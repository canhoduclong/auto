<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products</title>
    <style>
        .container {
            display: flex;
        }
        .categories {
            width: 20%;
            padding: 10px;
            border-right: 1px solid #ccc;
        }
        .products {
            width: 80%;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="categories">
            <h2>Categories</h2>
            <ul>
                @foreach($categories as $category)
                    <li>{{ $category->name }}</li>
                @endforeach
            </ul>
        </div>
        <div class="products">
            <h2>Products</h2>
            <form method="GET" action="{{ route('pages.products_by_category') }}">
                <label for="month">Month:</label>
                <input type="number" id="month" name="month" min="1" max="12" value="{{ request('month') }}">
                <label for="day">Day:</label>
                <input type="number" id="day" name="day" min="1" max="31" value="{{ request('day') }}">
                <button type="submit">Filter</button>
            </form>
            <hr>
            @foreach($products as $product)
                <h3>{{ $product->name }}</h3>
                @if($product->variants->count() > 0)
                    <ul>
                        @foreach($product->variants as $variant)
                            <li>{{ $variant->name }} - Price: {{ $variant->price }}</li>
                        @endforeach
                    </ul>
                @else
                    <p>No variants for this product.</p>
                @endif
            @endforeach
        </div>
    </div>
</body>
</html>
