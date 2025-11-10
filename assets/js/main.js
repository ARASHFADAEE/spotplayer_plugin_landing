let q = jQuery.noConflict();

q(document).ready(function () {
    const $form = q('#purchase-form');
    const $btnBuy = q('#btn-buy-ajax');
    const $modal = q('#card-modal');
    const $close = q('.spl-modal__close');
    const $submitCard = q('#splSubmitCard');
    const $feedback = q('#splModalFeedback');

    function openModal() {
        // Inject card info
        q('#splCardNumber').text(spotplayerLanding.card_number || '');
        q('#splCardHolder').text(spotplayerLanding.card_holder || '');
        $feedback.text('');
        $modal.attr('aria-hidden', 'false');
    }

    function closeModal() {
        $modal.attr('aria-hidden', 'true');
    }

    $close.on('click', function () { closeModal(); });

    $btnBuy.on('click', function (e) {
        e.preventDefault();
        const fullName = q('#fullName').val();
        const phone = q('#phone').val();
        const coupon = q('#coupon').length ? q('#coupon').val() : '';

        if (!fullName || !phone) {
            alert('لطفاً نام و شماره تماس را وارد کنید.');
            return;
        }

        if (Number(spotplayerLanding.is_card) === 1) {
            openModal();
            return;
        }

        // Default: add to cart and go to checkout
        const pid = Number(spotplayerLanding.product_id);
        if (pid > 0) {
            window.location.href = '?add-to-cart=' + pid;
        }
    });

    $submitCard.on('click', function () {
        const fullName = q('#fullName').val();
        const phone = q('#phone').val();
        const coupon = q('#coupon').length ? q('#coupon').val() : '';
        const fileInput = q('#receipt')[0];
        const file = (fileInput && fileInput.files && fileInput.files[0]) ? fileInput.files[0] : null;

        // Validate receipt file presence and type (JPG/PNG)
        if (!file) {
            $feedback.text('لطفاً تصویر رسید را انتخاب کنید.');
            return;
        }
        const allowedTypes = ['image/jpeg', 'image/png'];
        const nameLower = (file.name || '').toLowerCase();
        const hasValidExt = nameLower.endsWith('.jpg') || nameLower.endsWith('.jpeg') || nameLower.endsWith('.png');
        const hasValidMime = allowedTypes.indexOf(file.type) !== -1;
        if (!hasValidMime && !hasValidExt) {
            $feedback.text('فرمت تصویر باید JPG یا PNG باشد.');
            return;
        }
        // Max size 2MB
        const maxBytes = 2 * 1024 * 1024;
        if (typeof file.size === 'number' && file.size > maxBytes) {
            $feedback.text('حجم فایل باید حداکثر ۲ مگابایت باشد.');
            return;
        }

        const fd = new FormData();
        fd.append('action', 'spotplayer_landing_card_submit');
        fd.append('nonce', spotplayerLanding.nonce);
        fd.append('fullName', fullName);
        fd.append('phone', phone);
        fd.append('coupon', coupon);
        fd.append('receipt', file);

        $submitCard.prop('disabled', true);
        $feedback.text('در حال ثبت سفارش...');

        q.ajax({
            url: spotplayerLanding.ajax_url,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function (res) {
                $submitCard.prop('disabled', false);
                if (res && res.success) {
                    $feedback.text(res.data && res.data.message ? res.data.message : 'ثبت شد');
                    setTimeout(function () { closeModal(); }, 2000);
                } else {
                    const msg = (res && res.data && res.data.message) ? res.data.message : 'خطا در ثبت';
                    $feedback.text(msg);
                }
            },
            error: function () {
                $submitCard.prop('disabled', false);
                $feedback.text('خطا در ارتباط با سرور');
            }
        });
    });
});