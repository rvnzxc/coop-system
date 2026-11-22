<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Category;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        $name = trim((string) $request->input('name'));
        $request->merge(['name' => $name]);

        $existing = Category::whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])->first();

        if ($existing && $request->expectsJson()) {
            return response()->json([
                'id' => $existing->id,
                'name' => $existing->name,
            ]);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('categories', 'name')->where(function ($query) use ($name) {
                    $query->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)]);
                }),
            ],
        ], [
            'name.required' => 'Please enter a category name.',
            'name.string' => 'Please enter a valid category name.',
            'name.max' => 'Category name must not be longer than 50 characters.',
            'name.unique' => 'A category with this name already exists.',
        ]);

        $category = Category::create(['name' => $validated['name']]);

        AuditLogger::log(AuditLog::ACTION_CATEGORY_CREATED, 'category', $category->id, [
            'name' => $category->name,
            'username' => Auth::user()?->username,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $category->id,
                'name' => $category->name,
            ], 201);
        }

        return back()->with('success', "Category '{$category->name}' added successfully!");
    }

    public function destroy(Request $request, Category $category)
    {
        $itemCount = $category->items()->count();

        if ($itemCount > 0) {
            $message = "Cannot delete '{$category->name}': {$itemCount} item"
                .($itemCount === 1 ? '' : 's')." still use it.";

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->with('error', $message);
        }

        $categoryId = $category->id;
        $categoryName = $category->name;
        $category->delete();

        AuditLogger::log(AuditLog::ACTION_CATEGORY_DELETED, 'category', $categoryId, [
            'name' => $categoryName,
            'username' => Auth::user()?->username,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => "Category '{$categoryName}' deleted successfully!"]);
        }

        return back()->with('success', "Category '{$categoryName}' deleted successfully!");
    }
}
