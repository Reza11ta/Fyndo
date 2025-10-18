<?php

if (! defined('ABSPATH')) {
    exit;
}

class FYND_Admin
{
    public static function init()
    {
        if (! is_admin()) {
            return;
        }
        add_action('admin_menu', array(__CLASS__, 'register_menu'));
    }

    public static function register_menu()
    {
        add_menu_page(
            'تنظیمات فایندو',
            'فایندو',
            'manage_options',
            'fyndo-settings',
            array(__CLASS__, 'render_settings'),
            'dashicons-search',
            56
        );
    }

    public static function render_settings()
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Insufficient permissions', 'fyndo-ajax-search'));
        }

?>
        <div class="wrap fyndo-admin-wrap">
            <h1 style="font-size:28px;margin-bottom:6px;">فایندو — راهنمای سریع</h1>
            <div class="fyndo-admin-container" style="max-width:1100px;padding:18px;background:#fff;border-radius:10px;color:#222;font-size:15px;line-height:1.7;">
                <style>
                    .fyndo-admin-container {
                        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
                    }

                    .fyndo-grid {
                        display: flex;
                        gap: 20px;
                        align-items: flex-start
                    }

                    .fyndo-panel {
                        flex: 1;
                        background: transparent;
                        padding: 12px
                    }

                    .fyndo-box {
                        background: #f7f9fb;
                        padding: 14px;
                        border-radius: 10px;
                        margin-bottom: 14px
                    }

                    .fyndo-box h2 {
                        font-size: 18px;
                        margin: 0 0 8px 0
                    }

                    .fyndo-code {
                        background: #ffffff;
                        border: 1px solid #eee;
                        padding: 10px;
                        border-radius: 6px;
                        font-family: SFMono-Regular, Menlo, Monaco, monospace;
                        white-space: pre-wrap;
                        direction: ltr;
                        text-align: left
                    }

                    .fyndo-short {
                        display: flex;
                        gap: 8px;
                        align-items: center
                    }

                    .fyndo-copy {
                        background: #0a84ff;
                        color: #fff;
                        border: none;
                        padding: 6px 10px;
                        border-radius: 6px;
                        cursor: pointer
                    }

                    .fyndo-copy:active {
                        transform: translateY(1px)
                    }

                    .fyndo-note {
                        margin-top: 10px;
                        color: #555
                    }

                    .fyndo-desc {
                        margin: 6px 0 12px 0;
                        color: #444;
                        font-size: 14px
                    }
                </style>

                <div class="fyndo-grid">
                    <div class="fyndo-panel">
                        <div class="fyndo-box">
                            <h2>شورت‌کدها</h2>
                            <p>چند شورت‌کد آماده برای استفاده:</p>
                            <div style="margin-top:10px">
                                <div class="fyndo-short">
                                    <pre class="fyndo-code" id="sc-basic">[fyndo_ajax_search]</pre>
                                    <button class="fyndo-copy" data-copy="#sc-basic">کپی</button>
                                </div>
                                <div class="fyndo-desc">شورت‌کد پیش‌فرض: فرم جستجوی AJAX را نمایش می‌دهد (هم برای محصولات ووکامرس و هم نوشته‌ها).</div>
                                <div class="fyndo-short" style="margin-top:8px">
                                    <pre class="fyndo-code" id="sc-perpage">[fyndo_ajax_search results_per_page="5"]</pre>
                                    <button class="fyndo-copy" data-copy="#sc-perpage">کپی</button>
                                </div>
                                <div class="fyndo-desc">تنظیم تعداد نتایج: تعداد نتایج نمایش‌داده‌شده در هر درخواست را مشخص می‌کند (پیش‌فرض 5).</div>
                                <div class="fyndo-short" style="margin-top:8px">
                                    <pre class="fyndo-code" id="sc-placeholder">[fyndo_ajax_search placeholder_text="جستجو..."]</pre>
                                    <button class="fyndo-copy" data-copy="#sc-placeholder">کپی</button>
                                </div>
                                <div class="fyndo-desc">تغییر متن نگهدارنده: متن داخل فیلد جستجو را به هر رشته‌ای که بخواهید تغییر می‌دهد.</div>
                            </div>
                        </div>

                        <div class="fyndo-box">
                            <h2>نمونهٔ استفاده</h2>
                            <p>قرار دادن شورت‌کد در صفحه یا ویجت المنتور: در ویرایشگر متن/المنتور، شورت‌کد را درج کنید. مثال:</p>
                            <pre class="fyndo-code">[fyndo_ajax_search results_per_page="8" placeholder_text="جستجوی محصولات..."]</pre>
                        </div>
                    </div>

                    <div class="fyndo-panel">
                        <div class="fyndo-box">
                            <h2>چگونه کار می‌کند</h2>
                            <p>حداقل <strong>۳</strong> حرف تایپ کنید تا نتایج نمایش داده شوند. نتایج شامل عنوان، تصویر شاخص و در صورت وجود قیمت می‌باشند.</p>
                        </div>

                        <div class="fyndo-box">
                            <h2>راهنمای سریع تغییر CSS</h2>
                            <p>برای سفارشی‌سازی ظاهر کافی است فایل <code>assets/css/my-ajax-search.css</code> را ویرایش کنید یا در تم خود قواعد زیر را اضافه نمایید.</p>
                            <p class="fyndo-note">نمونه‌های متداول:</p>
                            <pre class="fyndo-code">/* تغییر اندازه و حاشیه ورودی */
.mas-search-form input[type="search"] { font-size:16px; padding:12px; border-radius:8px; }

/* ظاهر هر نتیجه */
.fyndo-search-results .fyndo-result-item { padding:10px; border-bottom:1px solid #f1f1f1; }
.fyndo-search-results .fyndo-result-title { font-size:15px; font-weight:600; }

/* تصویر کوچک */
.fyndo-search-results .fyndo-thumb { width:56px; height:56px; object-fit:cover; margin-right:12px; }</pre>
                            <p class="fyndo-note">نکات:</p>
                            <ul>
                                <li>اگر می‌خواهید فقط در صفحات المنتور تغییر دهید از سلکتور `.elementor-widget-fyndo_ajax_search` استفاده کنید.</li>
                                <li>برای افزودن فونت یا RTL بهتر، استایل‌های خود را در فایل child-theme یا در تنظیمات CSS سفارشی قرار دهید تا هنگام به‌روزرسانی افزونه حذف نشوند.</li>
                            </ul>
                        </div>

                        <div class="fyndo-box">
                            <h2>نکتهٔ مهم</h2>
                            <p class="fyndo-note">برای کار صحیح این افزونه لازم است ووکامرس یا المنتور نصب و فعال باشد. در صورت نیاز به کمک بیشتر، اطلاع دهید.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            (function() {
                function copyText(selector) {
                    var el = document.querySelector(selector);
                    if (!el) return;
                    var text = el.innerText || el.textContent;
                    navigator.clipboard && navigator.clipboard.writeText(text).then(function() {
                        alert('شورت‌کد کپی شد');
                    }, function() {
                        try {
                            var ta = document.createElement('textarea');
                            ta.value = text;
                            document.body.appendChild(ta);
                            ta.select();
                            document.execCommand('copy');
                            ta.remove();
                            alert('شورت‌کد کپی شد');
                        } catch (e) {
                            alert('کپی ناموفق بود');
                        }
                    });
                }
                document.querySelectorAll('.fyndo-copy').forEach(function(btn) {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        var sel = btn.getAttribute('data-copy');
                        copyText(sel);
                    });
                });
            })();
        </script>
<?php
    }
}
