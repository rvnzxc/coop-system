@extends('layouts.app')

@section('content')
<div style="max-width: 1100px; margin: 30px auto; padding: 0 20px; display: flex; flex-direction: column; gap: 20px;">
    
    <!-- TOP PROFILE CARD -->
    <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 30px; display: flex; align-items: center; gap: 25px;">
        <!-- Avatar -->
        <div style="width: 80px; height: 80px; border-radius: 50%; background: #1b3a1b; display: flex; align-items: center; justify-content: center; color: #d0ff00; font-size: 32px;">
            <i class="fas fa-user"></i>
        </div>
        
        <!-- Member Info -->
        <div style="flex: 1;">
            <h2 style="color: #1b3a1b; font-size: 26px; font-weight: bold; margin: 0 0 6px 0;">
                {{ $member->first_name }} {{ $member->last_name }}
            </h2>
            @if($member->email)
                <div style="color: #666; font-size: 14px; margin-bottom: 4px;">
                    <i class="fas fa-envelope" style="margin-right: 8px;"></i>{{ $member->email }}
                </div>
            @endif
            @if($member->phone)
                <div style="color: #666; font-size: 14px; margin-bottom: 4px;">
                    <i class="fas fa-phone" style="margin-right: 8px;"></i>{{ $member->phone }}
                </div>
            @endif
            <div style="margin-top: 8px; display: inline-block;">
                @if($member->is_active)
                    <span style="background: #e8f5e8; color: #2d5a2d; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: bold;">Active</span>
                @else
                    <span style="background: #ffe8e8; color: #d32f2f; padding: 4px 14px; border-radius: 20px; font-size: 12px; font-weight: bold;">Inactive</span>
                @endif
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div style="display: flex; gap: 10px; margin-left: auto;">
            <a href="{{ route('members.edit', $member->id) }}" style="background: #2196f3; color: #fff; padding: 8px 20px; border-radius: 6px; font-weight: bold; border: none; font-size: 13px; text-decoration: none; display: inline-block; transition: background 0.3s ease;" onmouseover="this.style.background='#1976d2'" onmouseout="this.style.background='#2196f3'">
                Edit
            </a>
            <a href="{{ route('members.card', $member->id) }}" style="background: #1b3a1b; color: #d0ff00; padding: 8px 20px; border-radius: 6px; font-weight: bold; border: none; font-size: 13px; text-decoration: none; display: inline-block; transition: background 0.3s ease;" onmouseover="this.style.background='#2d5a2d'" onmouseout="this.style.background='#1b3a1b'">
                <i class="fa fa-id-card"></i> Print Card
            </a>
            <form action="{{ route('members.destroy', $member->id) }}" method="POST" style="display: inline-block; margin: 0;">
                @csrf
                @method('DELETE')
                <button type="submit" style="background: #f44336; color: #fff; padding: 8px 20px; border-radius: 6px; font-weight: bold; border: none; font-size: 13px; cursor: pointer; transition: background 0.3s ease;" onmouseover="this.style.background='#d32f2f'" onmouseout="this.style.background='#f44336'" onclick="return confirm('Are you sure you want to delete this member?')">
                    Delete
                </button>
            </form>
        </div>
    </div>
    
    <!-- MIDDLE ROW -->
    <div style="display: flex; gap: 20px;">
        <!-- LEFT CARD - Quick Stats -->
        <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 25px; flex: 1;">
            <div style="font-size: 16px; font-weight: bold; color: #1b3a1b; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;">
                Quick Stats
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f8f8f8;">
                <span style="color: #666; font-size: 14px;">Member Since</span>
                <span style="color: #333; font-size: 14px; font-weight: bold;">{{ $member->created_at->format('F d, Y') }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f8f8f8;">
                <span style="color: #666; font-size: 14px;">Status</span>
                <span style="color: #333; font-size: 14px; font-weight: bold;">{{ $member->is_active ? 'Active' : 'Inactive' }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f8f8f8;">
                <span style="color: #666; font-size: 14px;">Last Purchase</span>
                <span style="color: #333; font-size: 14px; font-weight: bold;">{{ $lastPurchaseDate ? $lastPurchaseDate->format('F d, Y') : 'Never' }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px 0;">
                <span style="color: #666; font-size: 14px;">Total Purchases</span>
                <span style="color: #333; font-size: 14px; font-weight: bold;">₱{{ number_format($member->total_purchases, 2) }}</span>
            </div>
        </div>
        
        <!-- RIGHT CARD - Purchase Analytics -->
        <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 25px; flex: 1;">
            <div style="font-size: 16px; font-weight: bold; color: #1b3a1b; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;">
                Purchase Analytics
            </div>
            
            <div style="display: flex; gap: 12px;">
                <div style="background: #f8f9fa; border-radius: 8px; padding: 15px; text-align: center; flex: 1;">
                    <div style="font-size: 22px; font-weight: bold; color: #1b3a1b;">₱{{ number_format($totalPurchases, 2) }}</div>
                    <div style="font-size: 12px; color: #666; margin-top: 4px;">Total Purchases</div>
                </div>
                
                <div style="background: #f8f9fa; border-radius: 8px; padding: 15px; text-align: center; flex: 1;">
                    <div style="font-size: 22px; font-weight: bold; color: #1b3a1b;">{{ $purchaseCount }}</div>
                    <div style="font-size: 12px; color: #666; margin-top: 4px;">Transactions</div>
                </div>
                
                <div style="background: #f8f9fa; border-radius: 8px; padding: 15px; text-align: center; flex: 1;">
                    <div style="font-size: 22px; font-weight: bold; color: #1b3a1b;">₱{{ number_format($averagePurchase, 2) }}</div>
                    <div style="font-size: 12px; color: #666; margin-top: 4px;">Average</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- BOTTOM ROW -->
    <div style="display: flex; gap: 20px;">
        <!-- LEFT CARD - Purchase Behavior -->
        <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 25px; flex: 1;">
            <div style="font-size: 16px; font-weight: bold; color: #1b3a1b; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;">
                Purchase Behavior
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f8f8f8;">
                <span style="color: #666; font-size: 14px;">Frequency</span>
                <span style="color: #333; font-size: 14px; font-weight: bold;">{{ $purchaseCount > 10 ? 'High' : ($purchaseCount > 5 ? 'Medium' : 'Low') }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f8f8f8;">
                <span style="color: #666; font-size: 14px;">Average Value</span>
                <span style="color: #333; font-size: 14px; font-weight: bold;">₱{{ number_format($averagePurchase, 2) }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px 0;">
                <span style="color: #666; font-size: 14px;">Last Activity</span>
                <span style="color: #333; font-size: 14px; font-weight: bold;">
    @if($lastPurchaseDate)
        {{ $lastPurchaseDate->setTimezone('Asia/Manila')->format('M d, Y h:i A') }}
        {{-- Debug: {{ $lastPurchaseDate->toDateTimeString() }} --}}
    @else
        Never
    @endif
</span>
            </div>
        </div>
        
        <!-- RIGHT CARD - Member Status -->
        <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 25px; flex: 1;">
            <div style="font-size: 16px; font-weight: bold; color: #1b3a1b; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 2px solid #f0f0f0;">
                Member Status
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f8f8f8;">
                <span style="color: #666; font-size: 14px;">Loyalty Level</span>
                <span style="background: #e3f2fd; color: #1976d2; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                    @if($totalPurchases >= 10000)
                        Gold
                    @elseif($totalPurchases >= 5000)
                        Silver
                    @elseif($totalPurchases > 0)
                        Bronze
                    @else
                        New
                    @endif
                </span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f8f8f8;">
                <span style="color: #666; font-size: 14px;">Account Status</span>
                <span style="color: #333; font-size: 14px; font-weight: bold;">{{ $member->is_active ? 'Active' : 'Inactive' }}</span>
            </div>
            
            <div style="display: flex; justify-content: space-between; padding: 10px 0;">
                <span style="color: #666; font-size: 14px;">Member ID</span>
                <span style="font-family: monospace; font-size: 15px; color: #1b3a1b;">#{{ str_pad($member->id, 5, '0', STR_PAD_LEFT) }}</span>
            </div>
        </div>
    </div>
    
    <!-- RECOMMENDATION CARD (full width) -->
    <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 25px;">
        @if($purchaseCount == 0)
            <div style="display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-lightbulb" style="color: #f39c12; font-size: 20px;"></i>
                <div>
                    <div style="font-weight: bold; color: #333; margin-bottom: 5px;">Recommendation</div>
                    <div style="color: #666; font-size: 14px;">This member hasn't made any purchases yet. Consider sending promotional offers to encourage their first purchase.</div>
                </div>
            </div>
        @elseif($lastPurchaseDate && $lastPurchaseDate->diffInDays(now()) > 30)
            <div style="display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-exclamation-triangle" style="color: #e67e22; font-size: 20px;"></i>
                <div>
                    <div style="font-weight: bold; color: #333; margin-bottom: 5px;">Attention Needed</div>
                    <div style="color: #666; font-size: 14px;">This member hasn't purchased in over 30 days. Consider reaching out with special offers.</div>
                </div>
            </div>
        @else
            <div style="display: flex; align-items: center; gap: 15px;">
                <i class="fas fa-check-circle" style="color: #27ae60; font-size: 20px;"></i>
                <div>
                    <div style="font-weight: bold; color: #333; margin-bottom: 5px;">Great Member!</div>
                    <div style="color: #666; font-size: 14px;">This member is actively purchasing and engaged with your cooperative.</div>
                </div>
            </div>
        @endif
    </div>
    
    <!-- Back Button -->
    <div style="text-align: center;">
        <a href="{{ route('members.index') }}" style="background: #6c757d; color: #fff; padding: 12px 30px; border-radius: 6px; font-weight: bold; font-size: 14px; border: none; text-decoration: none; display: inline-block; transition: background 0.3s ease;" onmouseover="this.style.background='#5a6268'" onmouseout="this.style.background='#6c757d'">
            Back to Members
        </a>
    </div>
</div>
@endsection
