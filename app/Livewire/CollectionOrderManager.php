<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Collection;

class CollectionOrderManager extends Component
{
    public $collections = [];

    protected $listeners = ['reorderCollections' => 'saveOrder'];

    public function mount()
    {
        $this->collections = auth()->user()->collections()->orderBy('sort_order')->orderBy('created_at')->get()->map(function($c){
            return [
                'id' => $c->id,
                'name' => $c->name,
                'sort_order' => $c->sort_order,
            ];
        })->toArray();
    }

    public function saveOrder($orderedIds = [])
    {
        // $orderedIds is an array of collection IDs in new order
        foreach ($orderedIds as $index => $id) {
            Collection::where('id',$id)->where('user_id',auth()->id())->update(['sort_order' => $index + 1]);
        }
        $this->mount(); // refresh
        $this->dispatch('order-saved');
    }

    public function render()
    {
        return view('livewire.collection-order-manager');
    }
}

