@extends('layouts.app')

@section('content')
<div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 30px; max-width: 1200px; margin: 20px auto;">
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h2 style="color: #1b3a1b; font-size: 28px; font-weight: bold; margin: 0;">Members Management</h2>
        <div style="display: flex; gap: 15px; align-items: center;">
            <form action="{{ route('members.index') }}" method="GET" style="display: flex; gap: 10px;">
                <input type="text" name="search" placeholder="Search members..." value="{{ $search ?? '' }}" style="padding: 10px 15px; border: 1px solid #ddd; border-radius: 6px; width: 250px;">
                <button type="submit" style="background: #1b3a1b; color: #fff; padding: 10px 20px; border-radius: 6px; font-weight: bold; border: none; cursor: pointer;">Search</button>
            </form>
            <button onclick="location.href='{{ route('members.create') }}'" style="background: #27ae60; color: #fff; padding: 10px 20px; border-radius: 6px; font-weight: bold; border: none; cursor: pointer;">+ Add Member</button>
        </div>
    </div>

    <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow: hidden; max-height: calc(100vh - 200px); overflow-y: auto;">
        <table style="width: 100%; border-collapse: collapse; margin: 0;">
            <thead>
                <tr>
                    <th style="background: #1b3a1b; color: #fff; padding: 15px; text-align: left; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Name</th>
                    <th style="background: #1b3a1b; color: #fff; padding: 15px; text-align: left; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Email</th>
                    <th style="background: #1b3a1b; color: #fff; padding: 15px; text-align: left; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Phone</th>
                    <th style="background: #1b3a1b; color: #fff; padding: 15px; text-align: left; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Total Purchases</th>
                    <th style="background: #1b3a1b; color: #fff; padding: 15px; text-align: left; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Purchase Count</th>
                    <th style="background: #1b3a1b; color: #fff; padding: 15px; text-align: left; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Last Purchase</th>
                    <th style="background: #1b3a1b; color: #fff; padding: 15px; text-align: left; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Status</th>
                    <th style="background: #1b3a1b; color: #fff; padding: 15px; text-align: left; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($members as $member)
                <tr style="transition: background 0.3s ease;">
                    <td style="padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle;">{{ $member->first_name }} {{ $member->last_name }}</td>
                    <td style="padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle;">{{ $member->email ?? 'N/A' }}</td>
                    <td style="padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle;">{{ $member->phone ?? 'N/A' }}</td>
                    <td style="padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle;">₱{{ number_format($member->total_purchases, 2) }}</td>
                    <td style="padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle;">{{ $member->purchase_count }}</td>
                    <td style="padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle;">{{ $member->last_purchase_date ? $member->last_purchase_date->format('M d, Y') : 'Never' }}</td>
                    <td style="padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle;">
                        @if($member->is_active)
                            <span style="background: #e8f5e8; color: #2d5a2d; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold;">Active</span>
                        @else
                            <span style="background: #ffe8e8; color: #d32f2f; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: bold;">Inactive</span>
                        @endif
                    </td>
                    <td style="padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle;">
                        <div class="action-buttons">
                            <button class="btn-edit" onclick="location.href='{{ route('members.edit', $member->id) }}'">
                                <i class="fa fa-edit"></i> Edit
                            </button>
                            <button class="btn-view" onclick="location.href='{{ route('members.card', $member->id) }}'" style="background: #1b3a1b; color: #d0ff00; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; margin-right: 5px;">
                                <i class="fa fa-id-card"></i> Card
                            </button>
                            <form action="{{ route('members.destroy', $member->id) }}" method="POST" class="delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-delete" onclick="return confirm('Are you sure you want to delete this member?')">
                                    <i class="fa fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" style="text-align: center; padding: 60px 20px;">
                        <div style="text-align: center;">
                            <i class="fas fa-users" style="font-size: 48px; color: #ccc; margin-bottom: 20px; display: block;"></i>
                            <h4 style="font-size: 18px; color: #666; margin-bottom: 20px;">No members found</h4>
                            <p style="font-size: 18px; color: #666; margin-bottom: 20px;">Start by adding your first member.</p>
                            <button onclick="location.href='{{ route('members.create') }}'" style="background: #27ae60; color: #fff; padding: 10px 20px; border-radius: 6px; font-weight: bold; border: none; cursor: pointer;">Add Your First Member</button>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
// Add hover effect to table rows
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('tbody tr');
    rows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.background = '#f8f9fa';
        });
        row.addEventListener('mouseleave', function() {
            this.style.background = '';
        });
    });
});
</script>
@endsection
