<?php
// Prevent fatal if WooCommerce functions are not yet available
if (!function_exists('wc_get_product')) {
    echo '<div class="landing"><div class="box-sell" style="padding:16px">ووکامرس فعال نیست یا هنوز بارگذاری نشده است.</div></div>';
    return;
}
?>
    <main class="landing" role="main">
<?php if(!empty(get_option('product_id'))):
            $product_id=get_option('product_id');
            $product = wc_get_product($product_id);
$product_name = $product->get_title();
$product_terms = get_the_terms($product_id, 'product_cat');
$is_in_stock = $product->is_in_stock();
$sale_prie =number_format($product->get_sale_price());
$regulur_price = number_format($product->get_regular_price()) ;
$product_thumbnail_url = get_the_post_thumbnail_url($product_id, 'full');
            
            ?>

        <article class="box-sell" role="region" aria-label="<?php echo esc_html( $product_name ) ?>">


            <!-- هدر باکس: تصویر سمت راست، توضیحات سمت چپ -->
            <div class="box-header">
                <!-- تصویر کاور دوره -->
                <div class="img-banner">
                    <img src="<?php echo esc_url($product_thumbnail_url)?>" alt="کاور دوره جامع گرافیک حرفه‌ای">
                </div>

                <!-- عنوان و توضیحات دوره -->
                <div class="title-description">
                    <h1 class="product-title"><?php echo esc_html($product_name)?></h1>
                    <p class="product-desc"><?php echo esc_html($product->get_description())?></p>
                     


                    <!-- ناحیه قیمت؛ قیمت جدید و قدیم (خط‌خورده) -->
                    <div class="price" aria-label="قیمت دوره">
                    <?php if(isset($sale_prie)):?>
                        <strong class="price-current" aria-label="قیمت با تخفیف"><?php echo esc_html($sale_prie)?></strong>
                        <?php endif;?>
                        <span class="price-currency">تومان</span>
                        <del class="price-old" aria-label="قیمت قبل از تخفیف"><?php echo esc_html($regulur_price)?></del>
                    </div>
                </div>
            </div>

            <!-- فرم خرید: تمام‌عرض در پایین باکس -->
            <form class="purchase-form" id="purchase-form" method="post">
                <!-- فضاهای مخفی برای دسترس‌پذیری در کنار placeholder -->
                <div class="form-group">
                    <label for="fullName" class="sr-only">نام و نام خانوادگی</label>
                    <input id="fullName" name="fullName" type="text" inputmode="text" placeholder="نام و نام خانوادگی" required aria-required="true">
                </div>

                <div class="form-group">
                    <label for="phone" class="sr-only">شماره تماس</label>
                    <input id="phone" name="phone" type="tel" inputmode="tel" placeholder="شماره تماس" pattern="[0-9]{11}" aria-describedby="phoneHelp" required>
                </div>
        
                
                <?php if(!empty($options['opt-coupon-spot-land']) && $options['opt-coupon-spot-land'] == 1):?>

                <div class="form-group form-inline">
                    <label for="coupon" class="sr-only">کد تخفیف</label>
                    <input id="coupon" name="coupon" type="text" inputmode="text" placeholder="کد تخفیف">
                    <button type="button" class="btn btn-coupon" aria-label="بررسی کد تخفیف">بررسی کدتخفیف</button>
                </div>
                <?php endif?>

                <button value="<?php esc_html($product_id)?>" type="submit" id="btn-buy-ajax" class="btn btn-buy" aria-label="خرید دوره">خرید دوره</button>
            </form>

        </article>

        <!-- Modal: Card-to-card submit -->
        <div id="card-modal" class="spl-modal" aria-hidden="true" role="dialog" aria-labelledby="cardModalTitle">
            <div class="spl-modal__dialog">
                <div class="spl-modal__header">
                    <h2 id="cardModalTitle" class="spl-modal__title">پرداخت کارت به کارت</h2>
                    <button type="button" class="spl-modal__close" aria-label="بستن">×</button>
                </div>
                <div class="spl-modal__body">
                    <p class="spl-modal__desc">لطفاً مبلغ دوره را به کارت زیر واریز کنید و رسید را بارگذاری نمایید.</p>
                    <div class="spl-card-info">
                        <div><strong>شماره کارت:</strong> <span id="splCardNumber"></span></div>
                        <div><strong>به نام:</strong> <span id="splCardHolder"></span></div>
                    </div>
                    <div class="spl-upload">
                        <label for="receipt">آپلود تصویر رسید بانکی</label>
                        <input type="file" id="receipt" name="receipt" accept="image/*" />
                    </div>
                    <div class="spl-modal__actions">
                        <button type="button" class="btn btn-buy" id="splSubmitCard">ثبت رسید</button>
                    </div>
                    <div class="spl-modal__feedback" id="splModalFeedback" role="status" aria-live="polite"></div>
                </div>
            </div>
        </div>


        <?php endif?>
    </main>