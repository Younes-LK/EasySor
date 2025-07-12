<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قرارداد شماره {{ $contract->id }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Vazirmatn', sans-serif;
            background-color: #f9f9f9;
            color: #333;
            line-height: 2;
        }

        .contract-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 3rem;
            background-color: #fff;
            border: 1px solid #ddd;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
        }

        .contract-header,
        .contract-footer {
            text-align: center;
            margin-bottom: 2rem;
        }

        .contract-header h1 {
            font-size: 1.5rem;
            font-weight: bold;
            margin: 0;
        }

        .contract-date {
            text-align: left;
            margin-bottom: 1.5rem;
        }

        .parties-section {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .party-info {
            border: 1px solid #eee;
            padding: 1rem;
            border-radius: 5px;
        }

        .party-info h3 {
            font-weight: bold;
            font-size: 1.1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
        }

        .info-line {
            display: flex;
            margin-bottom: 0.5rem;
        }

        .info-line .label {
            font-weight: bold;
            min-width: 100px;
        }

        .info-line .value {
            border-bottom: 1px dotted #999;
            flex-grow: 1;
            padding: 0 0.5rem;
        }

        .material-section h3 {
            font-size: 1.2rem;
            font-weight: bold;
            margin-top: 2rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #333;
            padding-bottom: 0.5rem;
        }

        .signatures {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            margin-top: 4rem;
            text-align: center;
        }

        .print-button-container {
            text-align: center;
            margin: 2rem 0;
        }

        .print-button {
            padding: 0.75rem 1.5rem;
            background-color: #4f46e5;
            color: white;
            border: none;
            border-radius: 5px;
            font-family: 'Vazirmatn', sans-serif;
            font-size: 1rem;
            cursor: pointer;
        }

        @media print {
            body {
                background-color: #fff;
            }

            .contract-container {
                margin: 0;
                padding: 0;
                border: none;
                box-shadow: none;
            }

            .print-button-container {
                display: none;
            }
        }
    </style>
</head>

<body>

    @php
        // CORRECTED numberToWords function
        function numberToWords($number)
        {
            if ($number == 0) {
                return 'صفر';
            }

            $persian_ones = ['', 'یک', 'دو', 'سه', 'چهار', 'پنج', 'شش', 'هفت', 'هشت', 'نه'];
            $persian_teens = ['ده', 'یازده', 'دوازده', 'سیزده', 'چهارده', 'پانزده', 'شانزده', 'هفده', 'هجده', 'نوزده'];
            $persian_tens = ['', '', 'بیست', 'سی', 'چهل', 'پنجاه', 'شصت', 'هفتاد', 'هشتاد', 'نود'];
            $persian_hundreds = ['', 'یکصد', 'دویست', 'سیصد', 'چهارصد', 'پانصد', 'ششصد', 'هفتصد', 'هشتصد', 'نهصد'];
            $persian_levels = ['', 'هزار', 'میلیون', 'میلیارد', 'تریلیون'];

            if (!is_numeric($number)) {
                return '';
            }
            if ($number < 0) {
                return 'منفی ' . numberToWords(abs($number));
            }

            $number = (string) $number;
            $parts = [];
            $level = 0;

            while (strlen($number) > 0) {
                $part_str = '';
                $part = substr($number, -3);
                $number = substr($number, 0, -3);

                if ($part > 0) {
                    $part_int = (int) $part;
                    if ($part_int >= 100) {
                        $hundreds = intval($part_int / 100);
                        $part_str .= $persian_hundreds[$hundreds];
                        $part_int %= 100;
                        if ($part_int > 0) {
                            $part_str .= ' و ';
                        }
                    }
                    if ($part_int >= 20) {
                        $tens = intval($part_int / 10);
                        $part_str .= $persian_tens[$tens];
                        $part_int %= 10;
                        if ($part_int > 0) {
                            $part_str .= ' و ';
                        }
                    }
                    if ($part_int > 0) {
                        if ($part_int < 10) {
                            $part_str .= $persian_ones[$part_int];
                        } else {
                            $part_str .= $persian_teens[$part_int - 10];
                        }
                    }

                    if ($level > 0) {
                        $part_str .= ' ' . $persian_levels[$level];
                    }
                    array_unshift($parts, $part_str);
                }
                $level++;
            }
            return implode(' و ', $parts);
        }
    @endphp

    <div class="print-button-container">
        <button class="print-button" onclick="window.print()">چاپ قرارداد</button>
    </div>

    <div class="contract-container">
        <div class="contract-header">
            <h1>قرارداد فروش و نصب و راه‌اندازی آسانسور</h1>
        </div>

        <div class="contract-date">
            <strong>تاریخ:</strong> {{ $contract->formatted_created_at ?? '....................' }}
        </div>

        <div class="parties-section">
            <div class="party-info">
                <h3>مشخصات فروشنده (پیمانکار)</h3>
                <div class="info-line"><span class="label">شرکت:</span> <span
                        class="value">{{ $companyInfo['name'] }}</span></div>
                <div class="info-line"><span class="label">شماره ثبت:</span> <span
                        class="value">{{ $companyInfo['registration_number'] }}</span></div>
                <div class="info-line"><span class="label">به نشانی:</span> <span
                        class="value">{{ $companyInfo['address'] }}</span></div>
                <div class="info-line"><span class="label">نماینده:</span> <span
                        class="value">{{ $companyInfo['representative_name'] }}</span></div>
                <div class="info-line"><span class="label">سمت:</span> <span
                        class="value">{{ $companyInfo['representative_title'] }}</span></div>
                <div class="info-line"><span class="label">تلفن:</span> <span
                        class="value">{{ $companyInfo['phone'] }}</span></div>
            </div>
            <div class="party-info">
                <h3>مشخصات خریدار (کارفرما)</h3>
                <div class="info-line"><span class="label">آقای/شرکت:</span> <span
                        class="value">{{ $contract->customer->name ?? '' }}</span></div>
                <div class="info-line"><span class="label">فرزند:</span> <span
                        class="value">{{ $contract->customer->father_name ?? '' }}</span></div>
                <div class="info-line"><span class="label">به نشانی:</span> <span
                        class="value">{{ $contract->address->address ?? '' }}</span></div>
                <div class="info-line"><span class="label">تلفن:</span> <span
                        class="value">{{ $contract->customer->phone ?? '' }}</span></div>
            </div>
        </div>

        <div class="material-section">
            <h3>ماده یک: موضوع قرارداد</h3>
            <p>
                عبارت است از طراحی و تأمین کلیه‌ی قطعات و تجهیزات ۱ دستگاه آسانسور با
                {{ $contract->stop_count ?? '....' }} توقف به شرح مشخصات فنی پیوست که به امضاء طرفین قرارداد رسیده و
                جزء لاینفک این قرارداد جهت ارسال تجهیزات آسانسور به محل اجرای پروژه است.
                <br>
                <strong>آدرس حمل اجرای پروژه به آدرس:</strong>
                {{ $contract->address->address ?? '................................' }} می‌باشد.
            </p>
        </div>

        <div class="material-section">
            <h3>ماده دو: مبلغ قرارداد</h3>
            <p>
                مبلغ کل قرارداد عبارت است از: <strong>{{ number_format($contract->total_price * 10) }}</strong> ریال
                (معادل: <strong>{{ number_format($contract->total_price) }}</strong> تومان)
                <br>
                به حروف: <strong>{{ numberToWords($contract->total_price) }} تومان</strong>
            </p>
        </div>

        <div class="material-section">
            <h3>ماده سه: نحوه پرداخت</h3>
            <p>
                @if ($contract->payments->isNotEmpty())
                    پرداخت‌ها به شرح زیر می‌باشد:
                    <ul>
                        @foreach ($contract->payments as $payment)
                            <li>
                                <strong>{{ $payment->title }}:</strong> مبلغ {{ number_format($payment->amount) }}
                                تومان در تاریخ {{ $payment->formatted_paid_at }}
                            </li>
                        @endforeach
                    </ul>
                @else
                    پرداختی ثبت نشده است.
                @endif
            </p>
            <p class="text-xs">
                تبصره: در صورت تأخیر در پرداخت به موقع (حداکثر یک هفته پس از اجرای هر مرحله از ارسال اجناس) و یا طولانی
                شدن قرارداد بیش از دو برابر مدت زمان قرارداد از طرف خریدار به هر دلیل، به خودی خود از فروشنده در قبال
                اجراء به موقع تعهدات مربوط به این قرارداد رفع مسئولیت گردیده و در این صورت نیاز به توافق جدید با توجه به
                نرخ روز اجناس باقی‌مانده طبق تبصره ماده ده قرارداد خواهد بود.
            </p>
        </div>

        <div class="material-section">
            <h3>ماده چهار: مدت قرارداد</h3>
            <p>مدت زمان این قرارداد از تاریخ دریافت پیش پرداخت، در صورت مفاد مندرج در ماده‌های (۳) و (۷) و تحویل محل نصب
                برابر بندهای ۳ و ۴ ماده (۷) این قرارداد به مدت .................... کاری می‌باشد.</p>
        </div>

        <div class="material-section">
            <h3>توضیحات تکمیلی</h3>
            <p style="white-space: pre-wrap;">{{ $contract->description ?? 'توضیحات خاصی ثبت نشده است.' }}</p>
        </div>


        <div class="signatures">
            <div>
                <h4>فروشنده</h4>
                <p>{{ $companyInfo['name'] }}</p>
                <br><br>
                <p>مهر و امضاء</p>
            </div>
            <div>
                <h4>خریدار</h4>
                <p>{{ $contract->customer->name ?? '' }}</p>
                <br><br>
                <p>مهر و امضاء</p>
            </div>
        </div>

    </div>

</body>

</html>
