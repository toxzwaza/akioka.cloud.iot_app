<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Laravel') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .swiper {
            width: 100%;
            height: 400px;
        }
        .swiper-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8">
        <!-- スライドショー -->
        <div class="swiper">
            <div class="swiper-wrapper">
                <!-- スライド1 -->
                <div class="swiper-slide">
                    <img src="/images/slide1.jpg" alt="スライド1">
                </div>
                <!-- スライド2 -->
                <div class="swiper-slide">
                    <img src="/images/slide2.jpg" alt="スライド2">
                </div>
                <!-- スライド3 -->
                <div class="swiper-slide">
                    <img src="/images/slide3.jpg" alt="スライド3">
                </div>
            </div>
            
            <!-- ナビゲーションボタン -->
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
            
            <!-- ページネーション -->
            <div class="swiper-pagination"></div>
        </div>

        <!-- メインコンテンツ -->
        @yield('content')
    </div>
</body>
</html> 