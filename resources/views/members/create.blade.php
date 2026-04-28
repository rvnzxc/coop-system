@extends('layouts.app')

@section('content')
<div style="max-width: 600px; margin: 30px auto; padding: 0 20px;">
    <h2 style="color: #1b3a1b; font-size: 28px; font-weight: bold; margin-bottom: 25px;">Add New Member</h2>
    
    <div style="background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 30px;">
        <form action="{{ route('members.store') }}" method="POST">
            @csrf
            
            <div style="margin-bottom: 20px;">
                <label for="first_name" style="display: block; font-weight: bold; color: #333; font-size: 14px; margin-bottom: 6px;">
                    First Name <span style="color: #f44336;">*</span>
                </label>
                <input type="text" 
                       id="first_name" 
                       name="first_name" 
                       value="{{ old('first_name') }}" 
                       required
                       style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box; transition: border-color 0.3s ease;"
                       onfocus="this.style.borderColor='#1b3a1b'; this.style.outline='none';"
                       onblur="this.style.borderColor='#ddd';">
                @error('first_name')
                    <div style="color: #f44336; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</div>
                @enderror
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="last_name" style="display: block; font-weight: bold; color: #333; font-size: 14px; margin-bottom: 6px;">
                    Last Name <span style="color: #f44336;">*</span>
                </label>
                <input type="text" 
                       id="last_name" 
                       name="last_name" 
                       value="{{ old('last_name') }}" 
                       required
                       style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box; transition: border-color 0.3s ease;"
                       onfocus="this.style.borderColor='#1b3a1b'; this.style.outline='none';"
                       onblur="this.style.borderColor='#ddd';">
                @error('last_name')
                    <div style="color: #f44336; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</div>
                @enderror
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="email" style="display: block; font-weight: bold; color: #333; font-size: 14px; margin-bottom: 6px;">
                    Email
                </label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       value="{{ old('email') }}" 
                       placeholder="member@example.com"
                       style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box; transition: border-color 0.3s ease;"
                       onfocus="this.style.borderColor='#1b3a1b'; this.style.outline='none';"
                       onblur="this.style.borderColor='#ddd';">
                @error('email')
                    <div style="color: #f44336; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</div>
                @enderror
            </div>
            
            <div style="margin-bottom: 20px;">
                <label for="phone" style="display: block; font-weight: bold; color: #333; font-size: 14px; margin-bottom: 6px;">
                    Phone
                </label>
                <input type="tel" 
                       id="phone" 
                       name="phone" 
                       value="{{ old('phone') }}" 
                       placeholder="09123456789"
                       style="width: 100%; padding: 12px 15px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; box-sizing: border-box; transition: border-color 0.3s ease;"
                       onfocus="this.style.borderColor='#1b3a1b'; this.style.outline='none';"
                       onblur="this.style.borderColor='#ddd';">
                @error('phone')
                    <div style="color: #f44336; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</div>
                @enderror
            </div>
            
            <div style="display: flex; gap: 15px; margin-top: 10px;">
                <a href="{{ route('members.index') }}" 
                   style="background: #6c757d; color: #fff; padding: 12px 30px; border-radius: 6px; font-weight: bold; font-size: 14px; border: none; cursor: pointer; text-decoration: none; display: inline-block; transition: background 0.3s ease;"
                   onmouseover="this.style.background='#5a6268'"
                   onmouseout="this.style.background='#6c757d'">
                    Back to Members
                </a>
                <button type="submit" 
                        style="background: #27ae60; color: #fff; padding: 12px 30px; border-radius: 6px; font-weight: bold; font-size: 14px; border: none; cursor: pointer; transition: background 0.3s ease;"
                        onmouseover="this.style.background='#229954'"
                        onmouseout="this.style.background='#27ae60'">
                    Add Member
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
