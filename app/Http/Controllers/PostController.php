<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Tag;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function list()
    {
        $settings = Setting::all()->keyBy('key'); 
        $posts = Post::where('is_published', true)->latest()->paginate(10);
        $categories = PostCategory::all();
        $tags = Tag::all();

        return view('posts.index', compact('posts', 'categories', 'tags','settings'));
    }

    public function show(Post $post)
    {
        $settings = Setting::all()->keyBy('key'); 
        $otherPosts = Post::where('is_published', true)->where('id', '!=', $post->id)->latest()->take(5)->get();
        $categories = PostCategory::all();
        $tags = Tag::all();

        return view('posts.show', compact('post', 'otherPosts', 'categories', 'tags','settings'));
    }

    public function category(PostCategory $category)
    {
        $settings = Setting::all()->keyBy('key'); 
        $posts = $category->posts()->where('is_published', true)->latest()->paginate(10);
        $categories = PostCategory::all();
        $tags = Tag::all();

        return view('posts.category', compact('posts', 'category', 'categories', 'tags','settings'));
    }



     /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $posts = Post::all();
        return view('admin.posts.index', compact('posts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.posts.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'content' => 'required',
        ]);
        
        Post::create($request->all());

        return redirect()->route('admin.posts.index')
            ->with('success', 'Post created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post)
    {
        return view('admin.posts.edit', compact('post'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        $request->validate([
            'title' => 'required',
            'content' => 'required',
        ]);

        $post->update($request->all());

        return redirect()->route('admin.posts.index')
            ->with('success', 'Post updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        $post->delete();

        return redirect()->route('admin.posts.index')
            ->with('success', 'Post deleted successfully.');
    }
}