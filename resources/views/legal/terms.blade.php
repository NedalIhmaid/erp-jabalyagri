@extends('legal.layout')

@section('title', 'شروط الاستخدام')
@section('heading', 'شروط الاستخدام')
@section('heading_en', 'Terms of Service')

@section('content')
    <p>
        تحكم هذه الشروط استخدام نظام إدارة العمليات الداخلي الخاص بشركة {{ config('legal.company') }}.
        باستخدام النظام يوافق المستخدم على الالتزام بها.
    </p>

    <h2>نطاق الاستخدام</h2>
    <p>
        النظام مخصص حصراً لموظفي الشركة المصرح لهم لأغراض العمل، ويشمل رفع طلبات المبيعات واعتمادها،
        وتسجيل الزيارات الميدانية، وتقديم طلبات الموارد البشرية، وإعداد التقارير الإدارية.
        لا يجوز استخدامه لأي غرض شخصي أو تجاري خارج نطاق أعمال الشركة.
    </p>

    <h2>الحسابات وكلمات المرور</h2>
    <ul>
        <li>يُنشأ الحساب من قبل إدارة النظام ويرتبط بموظف واحد محدد.</li>
        <li>يتحمل الموظف مسؤولية الحفاظ على سرية بيانات دخوله وعدم مشاركتها مع أي شخص آخر.</li>
        <li>يجب إبلاغ إدارة النظام فوراً عند الاشتباه في وصول غير مصرح به.</li>
        <li>تُعطَّل الحسابات فور انتهاء العلاقة الوظيفية.</li>
    </ul>

    <h2>الاستخدام المقبول</h2>
    <ul>
        <li>إدخال بيانات صحيحة ودقيقة في الطلبات والزيارات والتقارير.</li>
        <li>عدم محاولة تجاوز الصلاحيات أو الوصول إلى بيانات لا تخص دور المستخدم.</li>
        <li>عدم إفشاء بيانات العملاء أو الموظفين خارج الشركة.</li>
        <li>عدم رفع أي محتوى غير متعلق بالعمل.</li>
    </ul>

    <h2>الإشعارات</h2>
    <p>
        يرسل النظام إشعارات تشغيلية عبر البريد الإلكتروني وداخل النظام وعبر واتساب إلى رقم الهاتف المسجل للموظف.
        هذه الإشعارات جزء من سير العمل وليست رسائل تسويقية. يمكن طلب إيقاف إشعارات واتساب عبر قسم الموارد البشرية.
        الرسائل الواردة إلى رقم الشركة على واتساب قد لا تُقرأ؛ تُستخدم القنوات الرسمية للتواصل.
    </p>

    <h2>ملكية البيانات</h2>
    <p>
        جميع البيانات المُدخلة في النظام، بما فيها طلبات المبيعات وسجلات الزيارات والتقارير، هي ملك للشركة.
        يُحتفظ بسجل تدقيق لكل عملية اعتماد أو رفض أو تعديل.
    </p>

    <h2>التوافر والمسؤولية</h2>
    <p>
        تسعى الشركة لإتاحة النظام بشكل مستمر دون أن تضمن خلوّه من الانقطاع أو الأخطاء.
        قد يتوقف النظام مؤقتاً لأعمال الصيانة أو التحديث. لا تتحمل الشركة مسؤولية أي قرار يُتخذ اعتماداً على
        بيانات أُدخلت بشكل غير صحيح من قبل المستخدمين.
    </p>

    <h2>إنهاء الوصول</h2>
    <p>
        يحق للشركة تعليق أو إنهاء وصول أي مستخدم عند مخالفة هذه الشروط أو انتهاء علاقته الوظيفية،
        دون الإخلال بأي إجراءات تأديبية أو قانونية أخرى.
    </p>

    <h2>القانون الواجب التطبيق</h2>
    <p>تخضع هذه الشروط لقوانين المملكة الأردنية الهاشمية، وتختص محاكم عمّان بالنظر في أي نزاع ينشأ عنها.</p>

    <h2>التعديلات</h2>
    <p>قد تُحدَّث هذه الشروط، ويُعد استمرار استخدام النظام بعد نشر التحديث قبولاً بها.</p>

    <section dir="ltr" lang="en">
        <h2>Terms of Service (English summary)</h2>
        <p>
            These terms govern use of the internal operations system of {{ config('legal.company') }}. Access is
            limited to authorised employees for business purposes: submitting and approving sales requests,
            logging field visits, filing HR requests, and producing management reports.
        </p>
        <p>
            <strong>Accounts:</strong> issued by system administrators to a named employee. Credentials must not
            be shared, suspected compromise must be reported immediately, and access is revoked when employment
            ends.
        </p>
        <p>
            <strong>Acceptable use:</strong> enter accurate data; do not attempt to exceed your assigned
            permissions or access records outside your role; do not disclose client or employee data outside the
            company; do not upload non-work content.
        </p>
        <p>
            <strong>Notifications:</strong> the system sends operational notifications by email, in-app, and
            WhatsApp to the employee's registered number. These form part of the approval workflow and are not
            marketing messages. Employees may opt out of WhatsApp through HR. Replies sent to the company
            WhatsApp number may not be monitored.
        </p>
        <p>
            <strong>Data ownership, availability and termination:</strong> all data entered belongs to the
            company and is subject to audit logging. The system is provided without a guarantee of uninterrupted
            or error-free operation, and may be unavailable during maintenance. The company may suspend access
            for breach of these terms. These terms are governed by the laws of the Hashemite Kingdom of Jordan,
            with the courts of Amman having jurisdiction.
        </p>
    </section>
@endsection
