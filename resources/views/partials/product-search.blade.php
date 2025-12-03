<section class="car-search-form  my-4">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="car-search-form__form">
                    <form action="{{ route('products.search') }}" method="GET">
                        <div class="row">
                            <div class="col-lg-10 col-md-10">
                                <input type="text" class="form-control" name="keyword" placeholder="Nhập từ khóa tìm kiếm sản phẩm..." value="{{ request('keyword') }}">
                            </div>
                            <div class="col-lg-2 col-md-2">
                                <button type="submit" class="site-btn btn-sm">Tìm kiếm</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
