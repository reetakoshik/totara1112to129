<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Automatically generated strings for Moodle installer
 *
 * Do not edit this file manually! It contains just a subset of strings
 * needed during the very first steps of installation. This file was
 * generated automatically using the
 * list of strings defined in /install/stringnames.txt.
 *
 * @package   installer
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['admindirname'] = 'مجلد الإدارة';
$string['availablelangs'] = 'حزم اللغة المتوفره';
$string['chooselanguagehead'] = 'اختر اللغة';
$string['chooselanguagesub'] = 'الرجاء حدد لغة للتثبيت. هذه اللغة ستستخدم أيضاً كاللغة الافتراضية للموقع، لكن يمكنك تغييرها لاحقا.';
$string['clialreadyconfigured'] = 'ملف التكوين config.php  موجود بالفعل. الرجاء استخدام admin/cli/install_database.php لتثبيت توتارا لهذا الموقع.';
$string['clialreadyinstalled'] = 'الملف config.php موجود مسبقاً، يرجى استخدام admin/cli/upgrade.php إن كنت تريد تحديث موقعك.';
$string['configfilewritten'] = 'تم انشاء ملف config.php بنجاح';
$string['databasehost'] = 'مستضيف قاعدة البيانات';
$string['databasename'] = 'اسم قاعدة البيانات';
$string['databasetypehead'] = 'اختيار برنامج تشغيل قاعدة البيانات';
$string['dataroot'] = 'مجلد البيانات';
$string['datarootpermission'] = 'صلاحيات بيانات المجلدات';
$string['dbprefix'] = 'مقدمة الجداول';
$string['dirroot'] = 'مجلد مودل';
$string['environmenthead'] = 'يتم فحص البيئة';
$string['errorsinenvironment'] = 'خطأ في البيئة!';
$string['installation'] = 'تثبيت';
$string['langdownloaderror'] = 'للأسف لغة &quot;{$a}&quot; لا يمكن تحميلها.  ستستمر عملية التثبيت باللغة الإنجليزية.';
$string['paths'] = 'مسارات';
$string['pathserrcreatedataroot'] = 'مجلد البيانات ({$a->dataroot}) لا يمكن إنشاؤه بواسطة المثبت.';
$string['pathshead'] = 'تأكيد المسارات';
$string['pathsrodataroot'] = 'لا يمكن الكتابة على مجلد Dataroot';
$string['pathsroparentdataroot'] = 'المجلد الأصلي ({$a->parent}) غير قابل للكتابة. مجلد البيانات ({$a->dataroot}) لا يمكن إنشاؤه بواسطة المثبت.';
$string['pathsunsecuredataroot'] = 'مجلد Dataroot غير آمن';
$string['pathswrongadmindir'] = 'مجلد Admin غير موجود';
$string['phpextension'] = 'تمديد {$a} PHP';
$string['phpversion'] = 'أصدار PHP';
$string['phpversionhelp'] = '<p> يتطلب مودل على الاقل الأصدار 4.3.0 لـ PHP </p>
<p> انت تستخدم الأصدار {$a} </p>
<p> يجب عليك ترقية PHP أو الانتقال إلى مستظيف أخر لديه أصدار اجد لـ PHP.</p>
في حالة وجود إصدار 5.0 فما بعد يمكنك الرجوع إلى إصدار 4.4 فما بعد';
$string['welcomep10'] = '{$a->installername} ({$a->installerversion})';
$string['welcomep20'] = 'أنت ترى هذه الصفحة لأنك قد قمت بتثبيت بنجاح وأطلقت حزمة <strong>{$a->packname} {$a->packversion}</strong> في جهاز الكمبيوتر الخاص بك. تهانينا!';
$string['welcomep40'] = 'تحتوي الحزمة أيضا على <strong>Totara {$a->moodlerelease} ({$a->moodleversion})</strong>.';
$string['welcomep50'] = 'يخضع استخدام كافة التطبيقات الموجودة في هذه الحزمة من قبل التراخيص الخاصة بكل منها. الحزمة الكاملة <strong>{$a->installername}</strong> هي <a href="http://www.opensource.org/docs/definition_plain.html">مفتوحة المصدر</a> ويتم توزيعها تحت ترخيص <a href="http://www.gnu.org/copyleft/gpl.html">GPL</a> .';
$string['wwwroot'] = 'WWW';
