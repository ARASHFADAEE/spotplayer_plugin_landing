به منظور ایجاد لایسنس توسط API آدرس زیر را به همراه هدر API$ و اطلاعات لایسنس با متد POST فراخوانی کنید:

https://panel.spotplayer.ir/license/edit/
توجه: امکان فراخوانی API به طور مستقیم از مرورگرها مانند کروم یا فایرفاکس وجود ندارد. در صورت انجام این کار به علت مکانیزم CORS مرورگر خطا روی میدهد. در مرورگر فقط امکان فراخوانی آدرس‌هایی وجود دارد که با دامنه آدرس صفحه فراخوان یکی باشند. برای تست API میتوانید از Postman استفاده کنید.

مقدار هدر API$ کلید شما است که از داشبورد پنل میتوانید کپی کنید. هدر LEVEL$ همیشه 1- ارسال شود.

$API: YhD5yX/9FQzVTg+c6YHQ7gCtZAs=
$LEVEL: -1
اطلاعات لایسنس می بایست به صورت JSON به سرور اسپات پلیر ارسال شوند:

{
	"test": false, // Test License
	"course": ["5d2ee35bcddc092a304ae5eb", "5d2ee35bcddc092a304ae5ec"],
	"offline": 30,
	"name": "customer",
	"payload": "",
	"data": {
		"confs": 0,
		"limit": {
			"5d2ee35bcddc092a304ae5eb": "0-",
			"5d2ee35bcddc092a304ae5ec": "1,4-6,10-"
		},
	},
	"watermark": {
		"position": 511,
			// Position Flag  [1     2    4]
			//                [8    16   32]
			//                [64  128  256]
			// Example: 1 | 2 | 4 | 8 | 16 | 64 | 128 | 256 = 511 [All Positions]
		"reposition": 15, // Seconds 1-3600
		"margin": 40, // Pixels 0-1000
		"texts": [
			{
				"text": "09121112266",
				"repeat": 10,
				"font": 1,
				"weight": 1,
				"color": 2164260863,
				"size": 50,
				"stroke": {"color": 2164260863, "size": 1}
			},
			{
				"text": "09121112266",
				"repeat": 1,
				"font": 1,
				"weight": 1,
				"color": 2164260863,
				"size": 200,
				"stroke": {"color": 2164260863, "size": 1}
			}
		]
	},
	"device": {
		"p0": 1, // All Devices 1-99
		"p1": 1, // Windows 0-99
		"p2": 0, // MacOS 0-99
		"p3": 0, // Ubuntu 0-99
		"p4": 0, // Android 0-99
		"p5": 0  // IOS 0-99
		"p6": 0  // WebApp 0-99
	}
}
مقدار payload یک رشته کاراکتر است که هنگامی که کاربر از سمت اسپات پلیر به صفحه پشتیبانی دوره هدایت میشود به صورت متغیر GET ارسال میگردد. برای مثال این مقدار را میتوانید برابر شناسه خرید کاربر در سیستم فروشگاهی خود قرار دهید.

از اطلاعات بالا فقط موارد course و name و watermark.texts.text الزامی میباشند و در صورت تعیین نشدن موارد دیگر، تنظیمات پیشفرض لایسنس تعیین شده در پنل استفاده میشوند.

برای مثال JSON زیر نیز برای ساخت لایسنس کافی میباشد:

{
	"course": ["5d2ee35bcddc092a304ae5eb"],
	"name": "customer",
	"watermark": {"texts": [{"text": "09121112266"}]}
}
برای ایجاد لایسنس تستی مقدار test را برابر true قرار دهید:

{
	"test": true,
	"course": ["5d2ee35bcddc092a304ae5eb"],
	"name": "customer",
	"watermark": {"texts": [{"text": "09121112266"}]}
}
پس از ایجاد لایسنس سرور مقادیر زیر را باز میگرداند.

{
	"_id": "5dcab540796f5d4d48a6570f", // Created License ID
	"key": "00015dcab540796f5d4d48a6570fb7bb74943c36c5e588c0267f9476ff7fe84846070ac971cd311716c6db6a6d603dae09b51395700894cd11c6dd10b71ae24625d1395595eb798844d7d5aec12c", // License Key
	"url": "/5e0796ae55fb7a18e83b3554/91d0726373dd525f9d3f57f688299a00/"
}
مقدار key کلید لایسنس ساخته شده میباشد، که باید در دیتابیس همراه سفارش ذخیره و همچنین در اختیار کاربر قرار بگیرد.

فراخوانی API ساخت لایسنس توسط Curl

curl -X POST https://panel.spotplayer.ir/license/edit/
	-H 'Content-Type: application/json' -H '$API: YhD5yX/9FQzVTg+c6YHQ7gCtZAs=' -H '$LEVEL: -1'
	-d '{"test": true, "course": ["5d2ee35bcddc092a304ae5eb"], "name": "ali", "watermark": {"texts": [{"text": "09121112266"}]}}'
نمونه کد PHP ساخت لایسنس

function filter($a): array {
	return array_filter($a, function ($v) { return !is_null($v); });
}

function request($u, $o = null) {
	curl_setopt_array($c = curl_init(), [
		CURLOPT_URL => $u,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CUSTOMREQUEST => $o ? 'POST' : 'GET',
		CURLOPT_SSL_VERIFYHOST => false,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_FOLLOWLOCATION => false,
		CURLOPT_HTTPHEADER => ['$API: ' . API, '$LEVEL: -1', 'content-type: application/json' ],
	]);
	if ($o) curl_setopt($c, CURLOPT_POSTFIELDS, json_encode(filter($o)));
	$json = json_decode(curl_exec($c), true);
	curl_close($c);
	if (is_array($json) && ($ex = @$json['ex'])) throw new Exception($ex['msg']);
	return $json;
}

function license($name, $courses, $watermarks, $test) {
	return request('https://panel.spotplayer.ir/license/edit/', [
		'test' => $test,
		'name' => $name,
		'course' => $courses,
		'watermark' => ['texts' => array_map(function ($w) { return ['text' => $w]; }, $watermarks)]
	]);
}

// ----------------------------------------------------------------------------
const API = 'YaEUZnSJcfpdQH3x4InV7QGoiQI=';
try {
	$L = license('ali', ['5d2ee35bcddc092a304ae5eb'], ['09121112266'], true);
	echo 'ID: ' . ($LID = $L['_id']);
	echo 'KEY: ' . $L['key'];
	echo 'URL: https://dl.spotplayer.ir/' . $L['url'];
}
catch (Exception $e) {
	echo($e->getMessage());
}