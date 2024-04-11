<?php

use Carbon\Carbon;

$date = Carbon::createFromDate(2023, 12, 31)->format('Y-m-d');
$part_reading_mark = 10;
$part_writing_mark = 8;
return [
    'YEAR_WEEKS' => array(
        array('title' => 'الاول من يناير', 'date' => $date, 'is_vacation' => 0),
        array('title' => 'الثاني من يناير', 'date' => Carbon::parse($date)->addWeeks()->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثالث من يناير', 'date' => Carbon::parse($date)->addWeeks(2)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الرابع من يناير', 'date' => Carbon::parse($date)->addWeeks(3)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الاول من فبراير', 'date' => Carbon::parse($date)->addWeeks(4)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثاني من فبراير', 'date' => Carbon::parse($date)->addWeeks(5)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثالث من فبراير', 'date' => Carbon::parse($date)->addWeeks(6)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الرابع من فبراير', 'date' => Carbon::parse($date)->addWeeks(7)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الاول من مارس', 'date' => Carbon::parse($date)->addWeeks(8)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثاني من مارس', 'date' => Carbon::parse($date)->addWeeks(9)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثالث من مارس', 'date' => Carbon::parse($date)->addWeeks(10)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الرابع من مارس', 'date' => Carbon::parse($date)->addWeeks(11)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الخامس من مارس', 'date' => Carbon::parse($date)->addWeeks(12)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الاول من ابريل', 'date' => Carbon::parse($date)->addWeeks(13)->format('Y-m-d'), 'is_vacation' => 1),
        array('title' => 'الثاني من ابريل', 'date' => Carbon::parse($date)->addWeeks(14)->format('Y-m-d'), 'is_vacation' => 1),
        array('title' => 'الثالث من ابريل', 'date' => Carbon::parse($date)->addWeeks(15)->format('Y-m-d'), 'is_vacation' => 1),
        array('title' => 'الرابع من ابريل', 'date' => Carbon::parse($date)->addWeeks(16)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الاول من مايو', 'date' => Carbon::parse($date)->addWeeks(17)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثاني من مايو', 'date' => Carbon::parse($date)->addWeeks(18)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثالث من مايو', 'date' => Carbon::parse($date)->addWeeks(19)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الرابع من مايو', 'date' => Carbon::parse($date)->addWeeks(20)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الاول من يونيو', 'date' => Carbon::parse($date)->addWeeks(21)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثاني من يونيو', 'date' => Carbon::parse($date)->addWeeks(22)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثالث من يونيو', 'date' => Carbon::parse($date)->addWeeks(23)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الرابع من يونيو', 'date' => Carbon::parse($date)->addWeeks(24)->format('Y-m-d'), 'is_vacation' => 1),
        array('title' => 'الخامس من يونيو', 'date' => Carbon::parse($date)->addWeeks(25)->format('Y-m-d'), 'is_vacation' => 1),
        array('title' => 'الاول من يوليو', 'date' => Carbon::parse($date)->addWeeks(26)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثاني من يوليو', 'date' => Carbon::parse($date)->addWeeks(27)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثالث من يوليو', 'date' => Carbon::parse($date)->addWeeks(28)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الرابع من يوليو', 'date' => Carbon::parse($date)->addWeeks(29)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الاول من اغسطس', 'date' => Carbon::parse($date)->addWeeks(30)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثاني من اغسطس', 'date' => Carbon::parse($date)->addWeeks(31)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثالث من اغسطس', 'date' => Carbon::parse($date)->addWeeks(32)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الرابع من اغسطس', 'date' => Carbon::parse($date)->addWeeks(33)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الخامس من اغسطس', 'date' => Carbon::parse($date)->addWeeks(34)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الاول من سبتمبر', 'date' => Carbon::parse($date)->addWeeks(35)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثاني من سبتمبر', 'date' => Carbon::parse($date)->addWeeks(36)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثالث من سبتمبر', 'date' => Carbon::parse($date)->addWeeks(37)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الرابع من سبتمبر', 'date' => Carbon::parse($date)->addWeeks(38)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الاول من اكتوبر', 'date' => Carbon::parse($date)->addWeeks(39)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثاني من اكتوبر', 'date' => Carbon::parse($date)->addWeeks(40)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثالث من اكتوبر', 'date' => Carbon::parse($date)->addWeeks(41)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الرابع من اكتوبر', 'date' => Carbon::parse($date)->addWeeks(42)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الاول من نوفمبر', 'date' => Carbon::parse($date)->addWeeks(43)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثاني من نوفمبر', 'date' => Carbon::parse($date)->addWeeks(44)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثالث من نوفمبر', 'date' => Carbon::parse($date)->addWeeks(45)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الرابع من نوفمبر', 'date' => Carbon::parse($date)->addWeeks(46)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الخامس من نوفمبر', 'date' => Carbon::parse($date)->addWeeks(47)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الاول من ديسمبر', 'date' => Carbon::parse($date)->addWeeks(48)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثاني من ديسمبر', 'date' => Carbon::parse($date)->addWeeks(49)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الثالث من ديسمبر', 'date' => Carbon::parse($date)->addWeeks(50)->format('Y-m-d'), 'is_vacation' => 0),
        array('title' => 'الرابع من ديسمبر', 'date' => Carbon::parse($date)->addWeeks(51)->format('Y-m-d'), 'is_vacation' => 0),
    ),
    'SUPPORT_MARK' => 10,
    'PART_READING_MARK' => $part_reading_mark,
    'PART_WRITING_MARK' => $part_writing_mark,
    'FULL_READING_MARK' => 50,
    'FULL_WRITING_MARK' => 40,
    "COMPLETE_THESIS_LENGTH" => 400,
    'ACCEPTED_STATUS' => 'accepted',
    'REJECTED_STATUS' => 'rejected',
    'PENDING_STATUS' => 'pending',
    'FINISHED_STATUS' => 'finished',
    'CANCELED_STATUS' => 'cancelled',
    'FREEZE_THIS_WEEK_TYPE' => 'تجميد الأسبوع الحالي',
    'FREEZE_NEXT_WEEK_TYPE' => 'تجميد الأسبوع القادم',
    'EXCEPTIONAL_FREEZING_TYPE' => 'تجميد استثنائي',
    'EXAMS_MONTHLY_TYPE' => 'نظام امتحانات - شهري',
    'EXAMS_SEASONAL_TYPE' => 'نظام امتحانات - فصلي',
    'WITHDRAWN_TYPE' => 'انسحاب مؤقت',
    'ARABIC_ROLES' => [
        'support_leader' => 'قائد دعم',
        'ambassador' => "سفير",
        'leader' => "قائد",
        'supervisor' => "مراقب",
        'advisor' => 'موجه',
        'consultant' => 'مستشار',
        'admin' => 'ادارة',
        'eligible_admin' => "مسؤول توثيق الكتب",
        'reviewer' => "مراجع توثيق",
        'auditor' => "مُقيم توثيق",
        'super_auditer' => "مسؤول تقييم التوثيقات",
        'super_reviewer' => "مسؤول مراجعة التوثيقات",
        'user_accept' => "مسؤول الوثائق",
        'marathon_coordinator' => "مسؤول عام في مارثون أصبوحة",
        'marathon_verification_supervisor' => "مسؤول تدقيق مارثون أصبوحة",
        'marathon_supervisor' => "مسؤول في مارثون أصبوحة",
        'marathon_ambassador' => "سفير مشارك في مارثون أصبوحة",
        'ramadan_coordinator'=> "مسؤول فعاليات رمضان",
        'ramadan_hadith_corrector' =>"مُصحح  مسابقة حديث",
        'ramadan_fiqh_corrector' => "مًصحح مسابقة فقه",
        'ramadan_tafseer_corrector' => "مُصحح مسابقة تفسير",
        'ramadan_vedio_corrector' =>"مُصحح مسابقة التثقيف بالفيديو",


    ],
    'FRONT_URL' => 'https://www.platform.osboha180.com',
    'ALL_SUPPER_ROLES' => ['admin', 'consultant', 'advisor', 'supervisor', 'leader'],
    'SUPERVISORANDABOVE_ROLES' => ['supervisor', 'advisor', 'consultant', 'admin'],
    'rolesToRetrieve' => array(
        'leader' => ['ambassador'],
        'supervisor' => ['ambassador', 'leader'],
        'advisor' => ['supervisor', 'support_leader', 'leader', 'ambassador'],
        'consultant' => ['advisor', 'supervisor', 'leader', 'support_leader', 'ambassador'],
        'admin' => ['admin', 'consultant', 'advisor', 'supervisor', 'leader', 'support_leader', 'ambassador', 'book_quality_team'],

    ),
    'ARABIC_MONTHS' => [
        '1' => 'يناير',
        '2' => 'فبراير',
        '3' => 'مارس',
        '4' => 'ابريل',
        '5' => 'مايو',
        '6' => 'يونيو',
        '7' => 'يوليو',
        '8' => 'اغسطس',
        '9' => 'سبتمبر',
        '10' => 'اكتوبر',
        '11' => 'نوفمبر',
        '12' => 'ديسمبر',
    ],
];
