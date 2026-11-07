<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Models\User;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Only allow specific fields to be updated (exclude username and email)
        $user->fill($request->only(['name', 'bio', 'phone', 'twitter', 'instagram', 'facebook']));

        $canUploadLogo = $user->hasFeature('upload_logo') || $user->isFeatureOverrideActive();

        // Handle logo upload
        if ($request->hasFile('logo')) {
            if (!$canUploadLogo) {
                return Redirect::route('profile.edit')->withErrors(['logo' => 'Logo uploads are available to subscribers only.']);
            }
            $request->validate([
                'logo' => 'image|mimes:png,jpg,jpeg,webp,svg|max:2048'
            ]);
            // Delete old logo if present
            if ($user->logo_path) {
                \Storage::disk('s3')->delete($user->logo_path);
                if ($user->logo_thumb_path) {
                    \Storage::disk('s3')->delete($user->logo_thumb_path);
                }
            }
            $file = $request->file('logo');
            $path = $file->store('logos', 's3');
            $user->logo_path = $path;
            // Create thumbnail (skip svg)
            if($file->getClientOriginalExtension() !== 'svg') {
                $image = \Intervention\Image\ImageManagerStatic::make($file)->resize(160, null, function($constraint){ $constraint->aspectRatio(); $constraint->upsize(); });
                $thumbName = 'logos/thumb_'.uniqid().'.'.$file->getClientOriginalExtension();
                \Storage::disk('s3')->put($thumbName, (string) $image->encode());
                $user->logo_thumb_path = $thumbName;
            }
        }


        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Display the portfolio customization form.
     */
    public function customize(Request $request): View
    {
        return view('profile.customize', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's portfolio customization.
     */
    public function updateCustomization(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'portfolio_font' => 'nullable|string|in:system,nunito,inter,playfair,roboto,open-sans,lato,montserrat,merriweather',
            'portfolio_accent_color' => 'nullable|regex:/^#([A-Fa-f0-9]{6})$/',
            'portfolio_theme' => 'nullable|in:auto,light,dark',
            'portfolio_background_color' => 'nullable|regex:/^#([A-Fa-f0-9]{6})$/',
            'portfolio_text_color' => 'nullable|regex:/^#([A-Fa-f0-9]{6})$/',
            'portfolio_heading_color' => 'nullable|regex:/^#([A-Fa-f0-9]{6})$/',
            'portfolio_display_mode' => 'nullable|in:grid,collections',
            'masonry_columns' => 'nullable|integer|min:2|max:6',
            'photos_per_page' => 'nullable|integer|min:10|max:100',
        ]);

        $request->user()->update([
            'portfolio_font' => $validated['portfolio_font'] ?? $request->user()->portfolio_font,
            'portfolio_accent_color' => $validated['portfolio_accent_color'] ?? $request->user()->portfolio_accent_color,
            'portfolio_theme' => $validated['portfolio_theme'] ?? $request->user()->portfolio_theme,
            'portfolio_background_color' => $validated['portfolio_background_color'] ?? null,
            'portfolio_text_color' => $validated['portfolio_text_color'] ?? null,
            'portfolio_heading_color' => $validated['portfolio_heading_color'] ?? null,
            'portfolio_display_mode' => $validated['portfolio_display_mode'] ?? $request->user()->portfolio_display_mode,
            'masonry_columns' => $validated['masonry_columns'] ?? $request->user()->masonry_columns,
            'photos_per_page' => $validated['photos_per_page'] ?? $request->user()->photos_per_page,
        ]);

        return Redirect::route('profile.customize')->with('status', 'portfolio-customized');
    }

    /**
     * Update the user's portfolio display mode.
     */
    public function updateDisplayMode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'portfolio_display_mode' => 'required|in:grid,collections',
            'masonry_columns' => 'required|integer|min:2|max:6',
            'photos_per_page' => 'required|integer|min:10|max:100',
            'portfolio_font' => 'nullable|string|in:system,nunito,inter,playfair,roboto,open-sans',
            'portfolio_accent_color' => 'nullable|regex:/^#([A-Fa-f0-9]{6})$/',
            'portfolio_theme' => 'nullable|in:auto,light,dark',
        ]);

        $request->user()->update([
            'portfolio_display_mode' => $validated['portfolio_display_mode'],
            'masonry_columns' => $validated['masonry_columns'],
            'photos_per_page' => $validated['photos_per_page'],
            'portfolio_font' => $validated['portfolio_font'] ?? $request->user()->portfolio_font,
            'portfolio_accent_color' => $validated['portfolio_accent_color'] ?? $request->user()->portfolio_accent_color,
            'portfolio_theme' => $validated['portfolio_theme'] ?? $request->user()->portfolio_theme,
        ]);

        return Redirect::route('profile.edit')->with('status', 'display-updated');
    }

    /**
     * Update the user's watermark settings.
     */
    public function updateWatermark(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'watermark_text' => 'nullable|string|max:50',
        ]);

        $request->user()->update([
            'watermark_text' => $validated['watermark_text']
        ]);

        return Redirect::route('profile.edit')->with('status', 'watermark-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current-password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * View the users account.
     */
    public function view(Request $request, $username): View
    {
        $user = User::where('username', $username)->first();

        if(!is_null($user)){
            // Determine which view to show based on user's preference
            if ($user->portfolio_display_mode === 'collections') {
                return $this->collectionsView($user);
            }

            // Default to grid view
            return view('profile.view', ['user' => $user]);
        }

        return abort(404);

    }

    /**
     * Public list of a photographer's collections, whatever their default
     * portfolio display mode is.
     */
    public function collections($username) : View
    {
        return $this->collectionsView(User::where('username', $username)->firstOrFail());
    }

    private function collectionsView(User $user): View
    {
        // Published, non-hidden, non-private collections with cover photos
        $collections = $user->collections()
            ->where('status', 'Published')
            ->where('hide_from_portfolio', false)
            ->where('private', false)
            ->with('coverPhoto')
            ->orderBy('event_date', 'desc')
            ->get();

        return view('profile.view-collections', [
            'user' => $user,
            'collections' => $collections,
        ]);
    }

    public function collection(Request $request, $username, $collection_id) : View
    {
        $user = User::where('username', $username)->firstOrFail();

        // Load collection with sets and filter photos based on privacy
        $collection = $user->collections()
            ->where('id', $collection_id)
            ->firstOrFail();

        if(is_null($collection)){
            abort(404);
        }

        // Handle password protection for private collections. Access is granted
        // either by a signed share link (emailed to the client) or by entering the
        // password once, after which the collection stays unlocked for the session.
        if ($collection->private && ! self::collectionIsUnlocked($request, $collection)) {
            if ($request->hasValidSignature()) {
                self::unlockCollection($request, $collection);
            } else {
                return view('collections.frontend.password', ['collection' => $collection, 'user' => $user]);
            }
        }

        // Load sets with photos - only show non-private photos unless it's a private collection
        $collection->load(['sets' => function($query) use ($collection) {
            $query->with(['photos' => function($q) use ($collection) {
                // If collection is private and user verified, show all photos
                // Otherwise only show public photos (private = 0)
                if (!$collection->private) {
                    $q->where('private', false);
                }
            }]);
        }]);

        return view('collections.frontend.show', ['collection' => $collection, 'user' => $user]);
    }

    /**
     * Verify a private collection password and unlock it for this session.
     */
    public function unlockCollectionWithPassword(Request $request, $username, $collection_id): RedirectResponse
    {
        $user = User::where('username', $username)->firstOrFail();
        $collection = $user->collections()->where('id', $collection_id)->firstOrFail();

        $request->validate(['password' => 'required|string']);

        if (! $collection->private || ! password_verify($request->input('password'), (string) $collection->password)) {
            return back()->withErrors(['password' => __('That password is not correct. Check the email from your photographer and try again.')]);
        }

        self::unlockCollection($request, $collection);

        return redirect()->route('profile.collection', ['username' => $username, 'collection_id' => $collection->id]);
    }

    public static function collectionIsUnlocked(Request $request, $collection): bool
    {
        if (! $collection->private) {
            return true;
        }
        if ($request->user() && $request->user()->id === $collection->user_id) {
            return true;
        }

        return in_array((string) $collection->id, $request->session()->get('unlocked_collections', []), true);
    }

    public static function unlockCollection(Request $request, $collection): void
    {
        $unlocked = $request->session()->get('unlocked_collections', []);
        $unlocked[] = (string) $collection->id;
        $request->session()->put('unlocked_collections', array_values(array_unique($unlocked)));
    }

    /**
     * Remove the user's logo.
     */
    public function removeLogo(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->logo_path) {
            \Storage::disk('s3')->delete($user->logo_path);
        }
        if ($user->logo_thumb_path) {
            \Storage::disk('s3')->delete($user->logo_thumb_path);
        }
        $user->logo_path = null;
        $user->logo_thumb_path = null;
        $user->save();
        return Redirect::route('profile.edit')->with('status', 'logo-removed');
    }
}
