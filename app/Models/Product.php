<?php

// app/Models/Product.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    // Allows these fields to be filled during 'Add Item'
    protected $fillable = ['item_name', 'quantity', 'price', 'category']; 
}