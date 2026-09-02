@php
    $categoryFallbacks = $isArabic
        ? ['الخدمات الحكومية', 'خدمات الأعمال', 'المرور والنقل', 'الوثائق والتفويضات']
        : ['Government services', 'Business services', 'Traffic & transport', 'Documents & powers'];
    $categoryIcons = ['landmark', 'briefcase', 'car', 'file'];
@endphp

<section class="quick-landing-section" id="services" aria-labelledby="quick-categories-title">
    <div class="quick-section-inner">
        <div class="quick-section-heading">
            <h2 id="quick-categories-title">{{ $isArabic ? 'التصنيفات الأكثر طلباً' : 'Popular categories' }}</h2>
            <a href="{{ route('service.list') }}">{{ $isArabic ? 'عرض كل الخدمات' : 'View all services' }} <span aria-hidden="true">←</span></a>
        </div>
        <div class="quick-category-grid">
            @forelse($landingCategories as $index => $category)
                <a class="quick-category-card" href="{{ route('category.detail', $category->id) }}">
                    <span class="quick-category-icon"><x-quick-icon :name="$categoryIcons[$index % count($categoryIcons)]" :size="19" /></span>
                    <strong>{{ $isArabic ? ($category->name_ar ?: $category->name) : ($category->name_en ?: $category->name) }}</strong>
                    <small>{{ $category->services_count }} {{ $isArabic ? 'خدمة' : 'services' }}</small>
                </a>
            @empty
                @foreach($categoryFallbacks as $index => $label)
                    <a class="quick-category-card" href="{{ route('service.list') }}">
                        <span class="quick-category-icon"><x-quick-icon :name="$categoryIcons[$index]" :size="19" /></span>
                        <strong>{{ $label }}</strong>
                    </a>
                @endforeach
            @endforelse
        </div>

        <div class="quick-section-heading quick-services-heading">
            <div><span>{{ $isArabic ? 'الخدمات الأعلى تقييماً' : 'Top-rated services' }}</span><h2>{{ $isArabic ? 'الخدمات الأكثر طلباً' : 'Most requested services' }}</h2></div>
            <a href="{{ route('service.list') }}">{{ $isArabic ? 'عرض كل الخدمات' : 'View all services' }} <span aria-hidden="true">←</span></a>
        </div>
        <div class="quick-service-grid">
            @forelse($landingServices as $service)
                @php
                    $serviceName = $isArabic ? ($service->name_ar ?: $service->name) : ($service->name_en ?: $service->name);
                    $serviceDescription = $service->description ?: ($isArabic ? 'خدمة موثوقة بمتابعة واضحة حتى الإنجاز.' : 'A verified service with clear tracking through completion.');
                @endphp
                <article class="quick-service-card">
                    <div class="quick-service-meta"><span class="quick-category-icon"><x-quick-icon name="file" :size="19" /></span><small>{{ optional($service->category)->name_ar ?: optional($service->category)->name }}</small></div>
                    <h3>{{ $serviceName }}</h3>
                    <p>{{ Illuminate\Support\Str::limit(strip_tags($serviceDescription), 88) }}</p>
                    <a href="{{ route('service.detail', $service->id) }}"><span>{{ $isArabic ? 'ابدأ الطلب' : 'Start request' }}</span><b aria-hidden="true">←</b></a>
                </article>
            @empty
                @foreach(($isArabic ? ['تجديد رخصة القيادة', 'تجديد الهوية والإقامة', 'الاستعلام عن المخالفات'] : ['Driving licence renewal', 'ID and residency renewal', 'Violation enquiry']) as $label)
                    <article class="quick-service-card"><div class="quick-service-meta"><span class="quick-category-icon"><x-quick-icon name="file" :size="19" /></span><small>{{ $isArabic ? 'خدمة موثقة' : 'Verified' }}</small></div><h3>{{ $label }}</h3><p>{{ $isArabic ? 'تنفيذ سريع مع تحديثات واضحة لحالة الطلب.' : 'Fast processing with clear request-status updates.' }}</p><a href="{{ route('service.list') }}"><span>{{ $isArabic ? 'ابدأ الطلب' : 'Start request' }}</span><b aria-hidden="true">←</b></a></article>
                @endforeach
            @endforelse
        </div>

        <div class="quick-feature-row">
            <div class="quick-bundle-card"><span>{{ $isArabic ? 'باقة خدمات' : 'Service bundle' }}</span><h3>{{ $isArabic ? 'باقة تأسيس الأعمال' : 'Business launch bundle' }}</h3><p>{{ $isArabic ? 'التسجيل والمستندات المطلوبة في طلب واحد متكامل.' : 'Registration and required documents reviewed in one tracked request.' }}</p><a href="{{ route('servicepackage.list') }}">{{ $isArabic ? 'عرض الباقات' : 'View bundle' }}</a></div>
            <div class="quick-rating-card"><strong>4.9/5 <span>★</span></strong><h3>{{ $isArabic ? 'عملاؤنا يثقون بكويك' : 'Customers trust Quick' }}</h3><p>{{ $isArabic ? 'تقييمات موثقة من العملاء' : 'Verified customer ratings' }}</p><div class="quick-avatar-row"><i>ع</i><i>م</i><i>س</i><i>+</i></div></div>
        </div>
    </div>
</section>

<section class="quick-how-section" id="how" aria-labelledby="quick-how-title">
    <div class="quick-section-inner quick-how-grid">
        <div class="quick-how-intro"><span>{{ $isArabic ? 'ثلاث خطوات فقط' : 'Only three steps' }}</span><h2 id="quick-how-title">{{ $isArabic ? 'من الطلب إلى الإنجاز، كل شيء يبقى واضحاً.' : 'From request to completion, everything stays clear.' }}</h2><p>{{ $isArabic ? 'لا إجراءات غامضة ولا اتصالات متكررة. تعرف على ما يحدث، وموعده، والجهة المسؤولة عن كل خطوة.' : 'No unclear processes or repeated calls. See what is happening, when, and who owns each step.' }}</p></div>
        <ol class="quick-step-grid">
            <li><b>01</b><strong>{{ $isArabic ? 'اختر الخدمة' : 'Choose a service' }}</strong><span>{{ $isArabic ? 'أخبرنا باحتياجك بإجابات بسيطة.' : 'Tell us what you need with a few simple answers.' }}</span></li>
            <li><b>02</b><strong>{{ $isArabic ? 'ارفع المستندات' : 'Upload documents' }}</strong><span>{{ $isArabic ? 'رفع آمن مع مراجعة مباشرة.' : 'Secure upload with immediate review.' }}</span></li>
            <li><b>03</b><strong>{{ $isArabic ? 'تابع الإنجاز' : 'Track completion' }}</strong><span>{{ $isArabic ? 'تحديثات فورية حتى اكتمال الطلب.' : 'Receive live notifications until completion.' }}</span></li>
        </ol>
    </div>
</section>

<section class="quick-app-section" id="app" aria-labelledby="quick-app-title">
    <div class="quick-section-inner quick-app-grid">
        <div class="quick-app-copy"><span>{{ $isArabic ? 'تجربة صُممت للجوال' : 'An experience built for mobile' }}</span><h2 id="quick-app-title">{{ $isArabic ? 'حمّل كويك. وأنجزها من أي مكان.' : 'Download Quick. Get it done anywhere.' }}</h2><p>{{ $isArabic ? 'استعرض الخدمات، ارفع المستندات بأمان، واستقبل تحديثات طلبك فوراً.' : 'Explore services, upload documents securely, and receive request updates instantly.' }}</p><div class="quick-store-buttons"><a href="#"><x-quick-icon name="apple" :size="25" /><span><small>Download on the</small><strong>App Store</strong></span></a><a href="#"><x-quick-icon name="play" :size="24" /><span><small>GET IT ON</small><strong>Google Play</strong></span></a></div></div>
        <div class="quick-app-device" aria-hidden="true"><div class="quick-phone quick-phone-track"><div class="quick-phone-island"></div><small>9:41</small><div class="quick-track-icon"><x-quick-icon name="check" :size="25" /></div><span>{{ $isArabic ? 'طلبك قيد المتابعة' : 'Your request is being tracked' }}</span><h3>{{ $isArabic ? 'متوقع الإنجاز غداً' : 'Expected tomorrow' }}</h3><p>#QK-2841</p><ol><li>{{ $isArabic ? 'تم استلام الطلب' : 'Request received' }}</li><li>{{ $isArabic ? 'مراجعة المستندات' : 'Document review' }}</li><li>{{ $isArabic ? 'الإصدار والتسليم' : 'Issuance and delivery' }}</li></ol></div></div>
    </div>
</section>

<section class="quick-partner-section" aria-labelledby="quick-partner-title">
    <div class="quick-section-inner"><div class="quick-partner-card"><div><span>{{ $isArabic ? 'شركاء موثوقون' : 'Verified partners' }}</span><h2 id="quick-partner-title">{{ $isArabic ? 'انضم إلى شبكة شركاء كويك' : 'Join the Quick partner network' }}</h2><p>{{ $isArabic ? 'أدر الطلبات المسندة وفريقك وجودة الخدمة من مساحة عمل آمنة.' : 'Manage assigned requests, your team and service quality through one secure workspace.' }}</p></div><a href="{{ route('login', ['portal' => 'partner']) }}">{{ $isArabic ? 'دخول الشركاء' : 'Partner sign in' }}</a></div></div>
</section>

<section class="quick-trust-section" id="trust" aria-labelledby="quick-trust-title">
    <div class="quick-section-inner quick-trust-card"><div><span>{{ $isArabic ? 'ثقة في كل خطوة' : 'Trust at every step' }}</span><h2 id="quick-trust-title">{{ $isArabic ? 'معاملتك الرسمية، في أيدٍ أمينة.' : 'Your official request, in safe hands.' }}</h2><p>{{ $isArabic ? 'بيانات مشفرة، مراجعة بشرية دقيقة، وشركاء خدمة موثوقون. صُممت كويك لتمنحك الثقة من أول نقرة.' : 'Encrypted data, careful human review, and verified service partners. Quick is designed for confidence from the first click.' }}</p></div><div class="quick-trust-metrics"><div><strong>+{{ number_format(max(12000, $landingMetrics['completed'])) }}</strong><span>{{ $isArabic ? 'طلب منجز' : 'Completed requests' }}</span></div><div><strong>4.9/5</strong><span>{{ $isArabic ? 'رضا العملاء' : 'Customer satisfaction' }}</span></div><div><strong>{{ max(98, $landingMetrics['services']) }}%</strong><span>{{ $isArabic ? 'إنجاز في الموعد' : 'On-time completion' }}</span></div><div><strong>24/7</strong><span>{{ $isArabic ? 'تحديثات الحالة' : 'Status updates' }}</span></div></div></div>
</section>
