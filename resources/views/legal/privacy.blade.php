@extends('legal.layout')

@section('title', 'سياسة الخصوصية')
@section('heading', 'سياسة الخصوصية')
@section('heading_en', 'Privacy Policy')

@section('content')
    <p>
        يوضح هذا المستند كيفية جمع ومعالجة وحماية البيانات داخل نظام إدارة العمليات الداخلي الخاص بشركة
        {{ config('legal.company') }}. النظام مخصص لموظفي الشركة المصرح لهم فقط، وليس خدمة متاحة للجمهور.
    </p>

    <h2>البيانات التي نجمعها</h2>
    <ul>
        <li><strong>بيانات الموظف:</strong> الاسم، البريد الإلكتروني، رقم الهاتف، الجنس، تاريخ التعيين، الدور الوظيفي، والمدير المباشر.</li>
        <li><strong>طلبات المبيعات:</strong> اسم العميل ورقم هاتفه وعنوانه، تفاصيل المنتجات والكميات والأسعار، وسجل مراحل الاعتماد.</li>
        <li><strong>الزيارات الميدانية:</strong> الموقع الجغرافي وقت تسجيل الزيارة، صور الزيارة، وملاحظات المهندس.</li>
        <li><strong>طلبات الموارد البشرية:</strong> نوع الطلب، تواريخ الإجازة، الأسباب، والمرفقات مثل التقارير الطبية.</li>
        <li><strong>سجل النشاط:</strong> عمليات الإنشاء والتعديل والاعتماد والرفض مع وقت تنفيذها والمستخدم الذي نفذها.</li>
    </ul>

    <h2>أغراض المعالجة</h2>
    <ul>
        <li>توجيه طلبات المبيعات عبر مراحل الاعتماد الأربع وطلبات الموارد البشرية عبر المدير المباشر والمدير العام.</li>
        <li>إشعار الموظفين بالإجراءات المطلوبة منهم أو بنتيجة طلباتهم.</li>
        <li>إعداد التقارير الإدارية ومتابعة الأداء.</li>
        <li>حفظ سجل تدقيق للمساءلة الداخلية.</li>
    </ul>

    <h2>الإشعارات عبر واتساب</h2>
    <p>
        نستخدم منصة WhatsApp Business المقدمة من شركة Meta لإرسال إشعارات تشغيلية إلى أرقام هواتف الموظفين
        المسجلة في النظام. تقتصر هذه الرسائل على معلومات الطلب مثل رقم الطلب واسم العميل ونوع الإجازة وحالة الاعتماد،
        ولا تُستخدم لأي أغراض تسويقية. تُعالج Meta هذه الرسائل بصفتها مزوّد خدمة وفقاً لشروطها.
        يمكن لأي موظف طلب إيقاف هذه الإشعارات بمراجعة قسم الموارد البشرية، مع استمرار وصول الإشعارات عبر البريد الإلكتروني والنظام.
    </p>

    <h2>مشاركة البيانات</h2>
    <p>
        لا نبيع البيانات ولا نشاركها لأغراض إعلانية. تقتصر المشاركة على مزوّدي الخدمة الضروريين لتشغيل النظام،
        وهم مزوّد الاستضافة ومزوّد خدمة الرسائل (Meta)، وعلى الجهات الرسمية عند وجود التزام قانوني.
    </p>

    <h2>الاحتفاظ بالبيانات</h2>
    <p>
        يُحتفظ بسجلات الطلبات والاعتمادات وسجل التدقيق طوال فترة عمل الموظف ولمدة لاحقة تفرضها المتطلبات المحاسبية
        والقانونية في المملكة الأردنية الهاشمية. تُعطّل حسابات الموظفين المنتهية خدماتهم فور انتهاء العلاقة الوظيفية.
    </p>

    <h2>الحماية</h2>
    <p>
        يتم الوصول إلى النظام عبر حسابات محمية بكلمات مرور مشفّرة وصلاحيات محددة حسب الدور الوظيفي.
        تُخزَّن المرفقات والصور في مساحة تخزين غير عامة، وتُسجَّل جميع العمليات الحساسة في سجل التدقيق.
    </p>

    <h2>حقوق الموظفين</h2>
    <p>
        يحق لكل موظف الاطلاع على بياناته الشخصية المسجلة وطلب تصحيحها. تُقدَّم هذه الطلبات إلى قسم الموارد البشرية
        أو عبر بريد التواصل أدناه.
    </p>

    <h2>التعديلات</h2>
    <p>قد تُحدَّث هذه السياسة عند تغيّر وظائف النظام. يُنشر تاريخ السريان المحدَّث أعلى هذه الصفحة.</p>

    <section dir="ltr" lang="en">
        <h2>Privacy Policy (English summary)</h2>
        <p>
            This system is an internal workforce and sales-approval application operated by
            {{ config('legal.company') }}. It is restricted to authorised employees and is not a
            consumer-facing service.
        </p>
        <p>
            <strong>Data collected:</strong> employee identity and contact details, reporting line and role;
            sales requests including client name, phone, address and order lines; field-visit records including
            GPS coordinates and photographs; HR requests including leave dates, reasons and attachments such as
            medical certificates; and an audit log of every create, update, approval and rejection.
        </p>
        <p>
            <strong>Purpose:</strong> routing sales requests through three approval stages and HR requests through
            the direct manager and general manager, notifying the responsible staff member, producing management
            reports, and maintaining an internal audit trail.
        </p>
        <p>
            <strong>WhatsApp:</strong> operational notifications are delivered to employees' registered phone
            numbers using the WhatsApp Business Platform provided by Meta. Message content is limited to request
            identifiers, client or employee names, request types and approval status. No marketing messages are
            sent. Meta processes these messages as a service provider. Employees may opt out through HR and will
            continue to receive email and in-app notifications.
        </p>
        <p>
            <strong>Sharing and retention:</strong> data is never sold or used for advertising. It is shared only
            with the hosting provider and Meta as processors, or where legally required. Records are retained for
            the duration of employment plus any period required by Jordanian accounting and labour law.
        </p>
        <p>
            <strong>Security and rights:</strong> access is controlled by per-role permissions and hashed
            credentials; uploaded files are stored in non-public storage. Employees may request access to, or
            correction of, their personal data via HR or the contact address below.
        </p>
    </section>
@endsection
