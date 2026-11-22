@extends('layouts.app')

@section('title', 'Member Card')

@section('content')
<style>
    .member-card-preview svg {
        display: block;
        width: 100%;
        height: auto;
    }
</style>

<div class="member-card-container flex min-h-[calc(100vh-8rem)] flex-col p-6">
    <div class="mb-4 flex justify-start">
        <x-button href="{{ route('members.index') }}" color="secondary" size="sm" aria-label="Back to Members">
            <x-icon name="arrow-left" class="h-3 w-3" /> Back
        </x-button>
    </div>

    <div class="flex flex-1 flex-col items-center justify-center gap-5">
        <x-button href="{{ route('members.card.download', $member->id) }}" color="primary" class="print-button" data-download-png>
            <x-download-icon class="h-3.5 w-3.5" /> Download Card
        </x-button>

        <div class="member-card-preview mx-auto w-full max-w-[350px] overflow-hidden rounded-[16px] shadow-xl" style="line-height: 0; aspect-ratio: 700 / 450;">
            @include('members.partials.member-card', ['member' => $member])
        </div>
    </div>
</div>
@endsection
