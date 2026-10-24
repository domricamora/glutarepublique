<?php

use App\Http\Controllers\Admin\AccountingController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\ApprovalController;
use App\Http\Controllers\Admin\AuditController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\ClinicSettingsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HrController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\LeadController as AdminLeadController;
use App\Http\Controllers\Admin\LegalPageController;
use App\Http\Controllers\Admin\PayrollController;
use App\Http\Controllers\Admin\PosController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\SetupController;
use App\Http\Controllers\Admin\StaffAccountController;
use App\Http\Controllers\Admin\TrafficController;
use App\Http\Controllers\KioskController;
use App\Http\Controllers\Site\BlogController;
use App\Http\Controllers\Site\BookingController;
use App\Http\Controllers\Site\ChatController as SiteChatController;
use App\Http\Controllers\Site\LeadController;
use App\Http\Controllers\Site\PageController;
use App\Http\Controllers\Site\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('treatments', [PageController::class, 'treatments'])->name('treatments.index');
Route::get('treatments/{slug}', [PageController::class, 'treatment'])->name('treatments.show');

Route::get('about', [PageController::class, 'about'])->name('about');
Route::get('doctors', [PageController::class, 'doctors'])->name('doctors');
Route::get('membership', [PageController::class, 'membership'])->name('membership');
Route::get('promotions', [PageController::class, 'promotions'])->name('promotions');
Route::get('before-after', [PageController::class, 'beforeAfter'])->name('before-after');
Route::get('contact', [PageController::class, 'contact'])->name('contact');
Route::get('journal', [BlogController::class, 'index'])->name('blog.index');
Route::get('journal/{slug}', [BlogController::class, 'show'])->name('blog.show');

Route::get('privacy-policy', [PageController::class, 'page'])->defaults('page', 'privacy-policy')->name('legal.privacy');
Route::get('terms', [PageController::class, 'page'])->defaults('page', 'terms')->name('legal.terms');
Route::get('data-privacy-notice', [PageController::class, 'page'])->defaults('page', 'data-privacy-notice')->name('legal.data-privacy');

Route::get('sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('robots.txt', [SeoController::class, 'robots'])->name('robots');
Route::get('llms.txt', [SeoController::class, 'llms'])->name('llms');

Route::get('book', [BookingController::class, 'create'])->name('book');
Route::get('book/slots', [BookingController::class, 'slots'])->middleware('throttle:60,1')->name('book.slots');
Route::post('book', [BookingController::class, 'store'])->middleware('throttle:10,1')->name('book.store');
Route::get('book/confirmed/{reference}', [BookingController::class, 'confirmed'])->middleware('signed')->name('book.confirmed');

Route::post('leads', [LeadController::class, 'store'])->middleware('throttle:10,1')->name('leads.store');

// The website chat window. A visitor is identified by a token in their own
// browser, not an account, and the throttle is per token rather than per
// address so a shared office does not lock everybody else out.
Route::get('chat/status', [SiteChatController::class, 'status'])->name('chat.status');
Route::get('chat/{token}', [SiteChatController::class, 'show'])->middleware('throttle:120,1')->name('chat.show');
Route::post('chat/{token}', [SiteChatController::class, 'store'])->name('chat.store');

// The branch DTR tablet: no sign-in, employee number and PIN instead.
Route::get('kiosk', [KioskController::class, 'index'])->name('kiosk.index');
Route::get('kiosk/{branch}', [KioskController::class, 'show'])->name('kiosk.show');
Route::post('kiosk/{branch}', [KioskController::class, 'punch'])->middleware('throttle:30,1')->name('kiosk.punch');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        // Who did what (plan.md §31). Read-only: there is no write route.
        Route::get('audit', [AuditController::class, 'index'])->middleware('can:settings.view')->name('audit.index');

        // The settings the clinic owns rather than the developer: its social
        // handles and whether the chat is on.
        Route::get('settings/clinic', [ClinicSettingsController::class, 'index'])->middleware('can:settings.edit')->name('settings.clinic.index');
        Route::patch('settings/clinic', [ClinicSettingsController::class, 'update'])->middleware('can:settings.edit')->name('settings.clinic.update');

        // Setup: load the reference and content rows the app needs to trade,
        // from the dashboard rather than a shell. Gated on settings.edit
        // because it writes lists that other roles can only read.
        Route::get('accounts', [StaffAccountController::class, 'index'])->middleware('can:settings.edit')->name('accounts.index');
        Route::post('accounts', [StaffAccountController::class, 'store'])->middleware('can:settings.edit')->name('accounts.store');
        Route::patch('accounts/{user}', [StaffAccountController::class, 'update'])->middleware('can:settings.edit')->name('accounts.update');

        Route::get('branches', [BranchController::class, 'index'])->middleware('can:settings.edit')->name('branches.index');
        Route::post('branches', [BranchController::class, 'store'])->middleware('can:settings.edit')->name('branches.store');
        Route::patch('branches/{branch}', [BranchController::class, 'update'])->middleware('can:settings.edit')->name('branches.update');

        // Requests only the owner grants. Everyone sees their own; deciding is owner only.
        Route::get('approvals', [ApprovalController::class, 'index'])->name('approvals.index');
        Route::post('approvals/{approval}', [ApprovalController::class, 'decide'])->middleware('can:approvals.decide')->name('approvals.decide');

        // Any member of staff on the roll files their own leave; the owner grants it.
        Route::post('my-leave', [HrController::class, 'fileOwnLeave'])->middleware('throttle:20,1')->name('my-leave.store');

        Route::get('traffic', [TrafficController::class, 'index'])->middleware('can:content.view')->name('traffic.index');

        Route::get('pages', [LegalPageController::class, 'index'])->middleware('can:content.edit')->name('pages.index');
        Route::patch('pages/{page}', [LegalPageController::class, 'update'])->middleware('can:content.edit')->name('pages.update');
        // POST for updates too: the forms carry a photo, and PHP only parses multipart bodies on POST.
        Route::get('promotions', [PromotionController::class, 'index'])->middleware('can:content.edit')->name('promotions.index');
        Route::post('promotions', [PromotionController::class, 'store'])->middleware('can:content.edit')->name('promotions.store');
        Route::post('promotions/{promotion}', [PromotionController::class, 'update'])->middleware('can:content.edit')->name('promotions.update');
        Route::get('journal', [PostController::class, 'index'])->middleware('can:content.edit')->name('posts.index');
        Route::post('journal', [PostController::class, 'store'])->middleware('can:content.edit')->name('posts.store');
        Route::post('journal/{post}', [PostController::class, 'update'])->middleware('can:content.edit')->name('posts.update');

        Route::get('setup', [SetupController::class, 'index'])->middleware('can:settings.edit')->name('setup.index');
        Route::post('setup/load', [SetupController::class, 'store'])->middleware('can:settings.edit')->name('setup.store');
        Route::post('setup/clear', [SetupController::class, 'clear'])->middleware('can:settings.edit')->name('setup.clear');

        // The front desk answering the website chat. Gated on the same
        // permission as leads, because a chat that has a name and a number on
        // it is an enquiry and belongs in the same queue.
        Route::get('chat', [ChatController::class, 'index'])->middleware('can:leads.view')->name('chat.index');
        Route::get('chat/{conversation}', [ChatController::class, 'show'])->middleware('can:leads.view')->name('chat.show');
        // What the inbox and the sidebar menu re-read on a timer, so a message that
        // arrives while somebody is on another screen still shows up.
        Route::get('chat-feed', [ChatController::class, 'poll'])->middleware('can:leads.view')->name('chat.poll');
        Route::get('chat/{conversation}/messages', [ChatController::class, 'messages'])->middleware('can:leads.view')->name('chat.messages');
        Route::post('chat/{conversation}/reply', [ChatController::class, 'reply'])->middleware('can:leads.edit')->name('chat.reply');
        Route::post('chat/{conversation}/close', [ChatController::class, 'close'])->middleware('can:leads.edit')->name('chat.close');

        Route::get('search', [AdminLeadController::class, 'search'])->middleware(['can:leads.view', 'throttle:120,1'])->name('search');
        Route::get('leads', [AdminLeadController::class, 'index'])->middleware('can:leads.view')->name('leads.index');
        Route::get('leads/create', [AdminLeadController::class, 'create'])->middleware('can:leads.create')->name('leads.create');
        Route::post('leads', [AdminLeadController::class, 'store'])->middleware('can:leads.create')->name('leads.store');
        Route::get('leads/{lead}', [AdminLeadController::class, 'show'])->middleware('can:leads.view')->name('leads.show');
        Route::patch('leads/{lead}', [AdminLeadController::class, 'update'])->middleware('can:leads.edit')->name('leads.update');
        Route::post('leads/{lead}/notes', [AdminLeadController::class, 'note'])->middleware('can:leads.edit')->name('leads.notes.store');

        Route::get('appointments', [AppointmentController::class, 'index'])->middleware('can:appointments.view')->name('appointments.index');
        Route::get('appointments/create', [AppointmentController::class, 'create'])->middleware('can:appointments.create')->name('appointments.create');
        Route::post('appointments', [AppointmentController::class, 'store'])->middleware('can:appointments.create')->name('appointments.store');
        Route::patch('appointments/{appointment}', [AppointmentController::class, 'update'])->middleware('can:appointments.edit')->name('appointments.update');

        // The counter: ring up a sale, take payment, print a receipt (plan.md 19).
        Route::get('pos', [PosController::class, 'index'])->middleware('can:pos.view')->name('pos.index');
        Route::post('pos', [PosController::class, 'store'])->middleware('can:pos.create')->name('pos.store');
        Route::get('pos/sales', [PosController::class, 'sales'])->middleware('can:pos.view')->name('pos.sales.index');
        Route::get('pos/sales-export', [PosController::class, 'export'])->middleware('can:pos.view')->name('pos.sales.export');
        Route::get('pos/sales/{sale}', [PosController::class, 'show'])->middleware('can:pos.view')->name('pos.sales.show');
        Route::post('pos/sales/{sale}/refund', [PosController::class, 'refund'])->middleware('can:pos.refund')->name('pos.sales.refund');

        // The shelves behind the counter: stock, lots, expiry, the ledger (plan.md 21, 22).
        Route::get('inventory', [InventoryController::class, 'index'])->middleware('can:inventory.view')->name('inventory.index');
        Route::get('inventory/receive', [InventoryController::class, 'createReceive'])->middleware('can:inventory.create')->name('inventory.receive');
        Route::post('inventory/receive', [InventoryController::class, 'receive'])->middleware('can:inventory.create')->name('inventory.receive.store');
        Route::get('inventory/adjust', [InventoryController::class, 'createAdjust'])->middleware('can:inventory.create')->name('inventory.adjust');
        Route::post('inventory/adjust', [InventoryController::class, 'adjust'])->middleware('can:inventory.create')->name('inventory.adjust.store');
        Route::get('inventory/products/{product}', [InventoryController::class, 'product'])->middleware('can:inventory.view')->name('inventory.product');
        Route::post('inventory/suppliers', [InventoryController::class, 'storeSupplier'])->middleware('can:inventory.create')->name('inventory.suppliers.store');

        // The catalogue: what the counter sells and the shelves are counted against.
        Route::get('catalog', [CatalogController::class, 'index'])->middleware('can:inventory.view')->name('catalog.index');
        Route::post('catalog', [CatalogController::class, 'store'])->middleware('can:inventory.create')->name('catalog.store');
        Route::patch('catalog/{product}', [CatalogController::class, 'update'])->middleware('can:inventory.create')->name('catalog.update');
        Route::delete('catalog/{product}', [CatalogController::class, 'destroy'])->middleware('can:inventory.create')->name('catalog.destroy');
        Route::post('catalog/products/{product}/photo', [CatalogController::class, 'productPhoto'])->middleware('can:inventory.create')->name('catalog.products.photo');
        Route::delete('catalog/products/{product}/photo', [CatalogController::class, 'removeProductPhoto'])->middleware('can:inventory.create')->name('catalog.products.photo.destroy');
        Route::post('catalog/services', [CatalogController::class, 'storeService'])->middleware('can:inventory.create')->name('catalog.services.store');
        Route::patch('catalog/services/{treatment}', [CatalogController::class, 'updateService'])->middleware('can:inventory.create')->name('catalog.services.update');
        Route::delete('catalog/services/{treatment}', [CatalogController::class, 'destroyService'])->middleware('can:inventory.create')->name('catalog.services.destroy');
        Route::post('branch', [BranchController::class, 'choose'])->name('branch.choose');
        Route::put('catalog/services/{treatment}/recipe', [CatalogController::class, 'saveRecipe'])->middleware('can:inventory.create')->name('catalog.services.recipe');
        Route::post('catalog/services/{treatment}/photo', [CatalogController::class, 'servicePhoto'])->middleware('can:inventory.create')->name('catalog.services.photo');
        Route::delete('catalog/services/{treatment}/photo', [CatalogController::class, 'removeServicePhoto'])->middleware('can:inventory.create')->name('catalog.services.photo.destroy');

        // People: the staff roll, the clock and time off (plan.md 28).
        Route::get('hr', [HrController::class, 'index'])->middleware('can:hr.view')->name('hr.index');
        Route::post('hr', [HrController::class, 'store'])->middleware('can:hr.create')->name('hr.store');
        Route::get('hr/attendance', [HrController::class, 'attendance'])->middleware('can:hr.view')->name('hr.attendance');
        Route::post('hr/attendance', [HrController::class, 'clock'])->middleware('can:hr.create')->name('hr.clock');
        Route::get('hr/leave', [HrController::class, 'leave'])->middleware('can:hr.view')->name('hr.leave');
        Route::post('hr/leave', [HrController::class, 'requestLeave'])->middleware('can:hr.create')->name('hr.leave.store');
        Route::patch('hr/leave/{leave}', [HrController::class, 'reviewLeave'])->middleware('can:approvals.decide')->name('hr.leave.review');
        Route::patch('hr/employees/{employee}', [HrController::class, 'update'])->middleware('can:hr.create')->name('hr.update');
        Route::post('hr/employees/{employee}/resign', [HrController::class, 'resign'])->middleware('can:hr.create')->name('hr.resign');
        Route::post('hr/employees/{employee}/reinstate', [HrController::class, 'reinstate'])->middleware('can:hr.create')->name('hr.reinstate');
        Route::post('hr/employees/{employee}/photo', [HrController::class, 'photo'])->middleware('can:hr.create')->name('hr.photo');
        Route::delete('hr/employees/{employee}/photo', [HrController::class, 'removePhoto'])->middleware('can:hr.create')->name('hr.photo.destroy');
        Route::get('hr/employees/{employee}', [HrController::class, 'show'])->middleware('can:hr.view')->name('hr.show');
        Route::patch('hr/employees/{employee}/kiosk-pin', [HrController::class, 'kioskPin'])->middleware('can:hr.create')->name('hr.kiosk-pin');
        Route::get('hr/attendance/{attendance}/photo', [HrController::class, 'attendancePhoto'])->middleware('can:hr.view')->name('hr.attendance.photo');
        Route::get('hr/schedule', [ScheduleController::class, 'index'])->middleware('can:hr.view')->name('hr.schedule');
        Route::post('hr/schedule', [ScheduleController::class, 'save'])->middleware('can:hr.create')->name('hr.schedule.save');
        Route::delete('hr/schedule', [ScheduleController::class, 'clear'])->middleware('can:hr.create')->name('hr.schedule.clear');
        Route::post('hr/schedule/copy', [ScheduleController::class, 'copyLastWeek'])->middleware('can:hr.create')->name('hr.schedule.copy');

        // Payroll: a run calculated from the records, then approved, then paid (plan.md 26).
        Route::get('payroll', [PayrollController::class, 'index'])->middleware('can:payroll.view')->name('payroll.index');
        Route::get('payroll/settings', [PayrollController::class, 'settings'])->middleware('can:payroll.view')->name('payroll.settings');
        Route::patch('payroll/settings', [PayrollController::class, 'updateSettings'])->middleware('can:payroll.create')->name('payroll.settings.update');
        Route::post('payroll/holidays', [PayrollController::class, 'storeHoliday'])->middleware('can:payroll.create')->name('payroll.holidays.store');
        Route::delete('payroll/holidays/{holiday}', [PayrollController::class, 'destroyHoliday'])->middleware('can:payroll.create')->name('payroll.holidays.destroy');
        Route::post('payroll/adjustments', [PayrollController::class, 'storeAdjustment'])->middleware('can:payroll.create')->name('payroll.adjustments.store');
        Route::post('payroll/adjustments/{adjustment}/clear', [PayrollController::class, 'clearAdjustment'])->middleware('can:payroll.create')->name('payroll.adjustments.clear');
        Route::post('payroll', [PayrollController::class, 'store'])->middleware('can:payroll.create')->name('payroll.store');
        Route::get('payroll/runs/{run}', [PayrollController::class, 'show'])->middleware('can:payroll.view')->name('payroll.show');
        Route::post('payroll/runs/{run}/approve', [PayrollController::class, 'approve'])->middleware('can:approvals.decide')->name('payroll.approve');
        Route::post('payroll/runs/{run}/pay', [PayrollController::class, 'pay'])->middleware('can:payroll.create')->name('payroll.pay');
        Route::get('payroll/payslips/{payslip}', [PayrollController::class, 'payslip'])->middleware('can:payroll.view')->name('payroll.payslip');

        // The books: a chart, a journal, and the statements that follow (plan.md 24).
        Route::get('accounting', [AccountingController::class, 'index'])->middleware('can:accounting.view')->name('accounting.index');
        Route::get('accounting/accounts', [AccountingController::class, 'accounts'])->middleware('can:accounting.view')->name('accounting.accounts');
        Route::get('accounting/entries', [AccountingController::class, 'entries'])->middleware('can:accounting.view')->name('accounting.entries');
        Route::post('accounting/entries', [AccountingController::class, 'store'])->middleware('can:accounting.create')->name('accounting.entries.store');
        Route::post('accounting/entries/{entry}/void', [AccountingController::class, 'void'])->middleware('can:accounting.create')->name('accounting.entries.void');
        Route::get('accounting/reports', [AccountingController::class, 'reports'])->middleware('can:accounting.view')->name('accounting.reports');
        Route::get('accounting/reports/export', [AccountingController::class, 'reportsExport'])->middleware('can:accounting.view')->name('accounting.reports.export');
        Route::post('accounting/periods/{period}/toggle', [AccountingController::class, 'togglePeriod'])->middleware('can:accounting.create')->name('accounting.periods.toggle');
    });
});

require __DIR__.'/settings.php';

// Last resort: any other single-segment path is a database backed page, or a
// 404. It has to stay below every named route above.
Route::get('{page}', [PageController::class, 'page'])
    ->where('page', '[a-z0-9-]+')
    ->name('legal');
