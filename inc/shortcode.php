    <main class="landing" role="main">
        <article class="box-sell" role="region" aria-label="فرم خرید دوره گرافیک حرفه‌ای">

            <!-- هدر باکس: تصویر سمت راست، توضیحات سمت چپ -->
            <div class="box-header">
                <!-- تصویر کاور دوره -->
                <div class="img-banner">
                    <img src="<?php echo LAND_PLUGIN_ASSETS_URL; ?>img/easy-cover-product.jpg" alt="کاور دوره جامع گرافیک حرفه‌ای">
                </div>

                <!-- عنوان و توضیحات دوره -->
                <div class="title-description">
                    <h1 class="product-title">دوره جامع گرافیک حرفه ای</h1>
                    <p class="product-desc">شما در این دوره به صورت کامل 0 تا 100 اصول طراحی را یاد می‌گیرید و می‌توانید وارد بازار کار شوید و کسب درآمد عالی را تجربه کنید.</p>

                    <!-- ناحیه قیمت؛ قیمت جدید و قدیم (خط‌خورده) -->
                    <div class="price" aria-label="قیمت دوره">
                        <strong class="price-current" aria-label="قیمت با تخفیف">185,000</strong>
                        <span class="price-currency">تومان</span>
                        <del class="price-old" aria-label="قیمت قبل از تخفیف">998,000</del>
                    </div>
                </div>
            </div>

            <!-- فرم خرید: تمام‌عرض در پایین باکس -->
            <form class="purchase-form" action="#" method="post" novalidate>
                <!-- فضاهای مخفی برای دسترس‌پذیری در کنار placeholder -->
                <div class="form-group">
                    <label for="fullName" class="sr-only">نام و نام خانوادگی</label>
                    <input id="fullName" name="fullName" type="text" inputmode="text" placeholder="نام و نام خانوادگی" required aria-required="true">
                </div>

                <div class="form-group">
                    <label for="phone" class="sr-only">شماره تماس</label>
                    <input id="phone" name="phone" type="tel" inputmode="tel" placeholder="شماره تماس" pattern="[0-9]{11}" aria-describedby="phoneHelp" required>
                </div>


                <?php if(!empty(get_option('is_copon')) && get_option('is_copon') === 1):?>

                <div class="form-group form-inline">
                    <label for="coupon" class="sr-only">کد تخفیف</label>
                    <input id="coupon" name="coupon" type="text" inputmode="text" placeholder="کد تخفیف">
                    <button type="button" class="btn btn-coupon" aria-label="بررسی کد تخفیف">بررسی کدتخفیف</button>
                </div>
                <?php endif?>

                <button type="submit" class="btn btn-buy" aria-label="خرید دوره">خرید دوره</button>
            </form>

        </article>
    </main>