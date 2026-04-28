@extends('layouts.app')

@section('title', 'Edit Member')

@section('content')
<div style="max-width: 600px; margin: 30px auto; padding: 0 20px;">
    <!-- Page Header -->
    <div style="margin-bottom: 20px;">
        <h2 style="color: #1b3a1b; font-size: 24px; font-weight: bold; margin: 0;">Edit Member</h2>
        <p style="color: #666; font-size: 14px; margin: 5px 0 0 0;">Update member information</p>
    </div>

    <!-- Form Card -->
    <div style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 30px;">
        <form action="{{ route('members.update', $member->id) }}" method="POST">
            @csrf
            @method('PUT')
            
            <!-- Form Row 1 -->
            <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                <div style="flex: 1;">
                    <label style="display: block; font-size: 14px; font-weight: bold; color: #333; margin-bottom: 6px;">
                        First Name <span style="color: #d32f2f;">*</span>
                    </label>
                    <input type="text" 
                           name="first_name" 
                           value="{{ old('first_name', $member->first_name) }}" 
                           required
                           style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box;"
                           placeholder="Enter first name">
                    @error('first_name')
                        <div style="color: #d32f2f; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
                
                <div style="flex: 1;">
                    <label style="display: block; font-size: 14px; font-weight: bold; color: #333; margin-bottom: 6px;">
                        Last Name <span style="color: #d32f2f;">*</span>
                    </label>
                    <input type="text" 
                           name="last_name" 
                           value="{{ old('last_name', $member->last_name) }}" 
                           required
                           style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box;"
                           placeholder="Enter last name">
                    @error('last_name')
                        <div style="color: #d32f2f; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <!-- Form Row 2 -->
            <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                <div style="flex: 1;">
                    <label style="display: block; font-size: 14px; font-weight: bold; color: #333; margin-bottom: 6px;">
                        Email
                    </label>
                    <input type="email" 
                           name="email" 
                           value="{{ old('email', $member->email) }}" 
                           style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box;"
                           placeholder="member@example.com">
                    @error('email')
                        <div style="color: #d32f2f; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
                
                <div style="flex: 1;">
                    <label style="display: block; font-size: 14px; font-weight: bold; color: #333; margin-bottom: 6px;">
                        Phone
                    </label>
                    <input type="tel" 
                           name="phone" 
                           value="{{ old('phone', $member->phone) }}" 
                           style="width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box;"
                           placeholder="09123456789">
                    @error('phone')
                        <div style="color: #d32f2f; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            
            <!-- Active Status -->
            <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" 
                           name="is_active" 
                           value="1"
                           {{ old('is_active', $member->is_active) ? 'checked' : '' }}
                           style="margin-right: 8px;">
                    <span style="font-size: 14px; color: #333;">Active Member</span>
                </label>
            </div>
            
            <!-- Member Information -->
            <div style="background: #f8f9fa; border-radius: 6px; padding: 15px; margin-bottom: 20px;">
                <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #666; font-weight: bold;">Member Information</h4>
                <div style="display: flex; gap: 20px;">
                    <div>
                        <span style="font-size: 12px; color: #666;">Member ID:</span><br>
                        <span style="font-size: 14px; font-weight: bold; color: #1b3a1b;">{{ $member->member_number }}</span>
                    </div>
                    <div>
                        <span style="font-size: 12px; color: #666;">Total Purchases:</span><br>
                        <span style="font-size: 14px; font-weight: bold; color: #1b3a1b;">₱{{ number_format($member->total_purchases, 2) }}</span>
                    </div>
                    <div>
                        <span style="font-size: 12px; color: #666;">Purchase Count:</span><br>
                        <span style="font-size: 14px; font-weight: bold; color: #1b3a1b;">{{ $member->purchase_count }}</span>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px;">
                <a href="{{ route('members.index') }}" 
                   style="display: inline-flex; align-items: center; padding: 10px 20px; background: #f5f5f5; color: #666; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: bold; transition: background 0.3s ease;"
                   onmouseover="this.style.background='#e0e0e0'" 
                   onmouseout="this.style.background='#f5f5f5'">
                    <i class="fa fa-arrow-left" style="margin-right: 6px;"></i> Back to Members
                </a>
                
                <div style="display: flex; gap: 10px;">
                    <a href="{{ route('members.analytics', $member->id) }}" 
                       style="display: inline-flex; align-items: center; padding: 10px 20px; background: #1b3a1b; color: #d0ff00; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: bold; transition: background 0.3s ease;"
                       onmouseover="this.style.background='#2d5a2d'" 
                       onmouseout="this.style.background='#1b3a1b'">
                        <i class="fa fa-chart-line" style="margin-right: 6px;"></i> View Analytics
                    </a>
                    <button type="submit" 
                            style="display: inline-flex; align-items: center; padding: 10px 20px; background: #1b3a1b; color: #d0ff00; border: none; border-radius: 6px; font-size: 14px; font-weight: bold; cursor: pointer; transition: background 0.3s ease;"
                            onmouseover="this.style.background='#2d5a2d'" 
                            onmouseout="this.style.background='#1b3a1b'">
                        <i class="fa fa-save" style="margin-right: 6px;"></i> Update Member
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
