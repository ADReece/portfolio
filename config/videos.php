<?php

/*
 * Video galleries (App\Videos). Files go from the browser straight to the
 * S3 bucket in parts; nothing is transcoded (the server has little memory):
 * H.264 or H.265 in MP4/MOV only, with a poster frame taken by ffmpeg.
 */
return [
    // Per plan (slug): the largest single file and the total, in MB.
    'plans' => [
        'videographer' => [
            'max_file_mb' => (int) env('VIDEO_MAX_FILE_MB', 4096),
            'max_total_mb' => (int) env('VIDEO_MAX_TOTAL_MB', 102400),
        ],
    ],
    // Admins and feature overrides get this.
    'unlimited' => ['max_file_mb' => 10240, 'max_total_mb' => 1048576],

    'part_size_mb' => 16,           // S3 needs at least 5 MB per part
    'extensions' => ['mp4', 'm4v', 'mov'],
    'mimes' => ['video/mp4', 'video/quicktime', 'video/x-m4v'],
    'codecs' => ['h264', 'hevc'],

    'ffmpeg' => env('FFMPEG_PATH', 'ffmpeg'),
    'ffprobe' => env('FFPROBE_PATH', 'ffprobe'),

    // Signed links for playback and posters.
    'playback_minutes' => 240,
];
