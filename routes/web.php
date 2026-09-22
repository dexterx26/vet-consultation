<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Client;
use App\Http\Controllers\Vet;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Consultation;

// Landing / Redirect
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->isAdmin()) return redirect()->route('admin.dashboard');
        if ($user->isVet()) return redirect()->route('vet.dashboard');
        return redirect()->route('client.dashboard');
    }
    return redirect()->route('login');
});

// Guest Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register/client', [AuthController::class, 'showRegisterClient'])->name('register.client');
    Route::post('/register/client', [AuthController::class, 'registerClient']);
    Route::get('/register/vet', [AuthController::class, 'showRegisterVet'])->name('register.vet');
    Route::post('/register/vet', [AuthController::class, 'registerVet']);
});

// Auth Common Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ----------------------------------------------------
    // CLIENT ROUTES
    // ----------------------------------------------------
    Route::middleware('role:client')->prefix('client')->name('client.')->group(function () {
        Route::get('/dashboard', [Client\DashboardController::class, 'index'])->name('dashboard');
        Route::get('/credits-balance', [Client\DashboardController::class, 'getCredits'])->name('credits-balance');

        // Pets CRUD
        Route::resource('pets', Client\PetController::class);

        // Veterinarian Search, Profile & Real-Time Availability Slots
        Route::get('/vets', [Client\VetSearchController::class, 'index'])->name('vets.search');
        Route::get('/vets/{vet}', [Client\VetSearchController::class, 'show'])->name('vets.show');
        Route::get('/vets/{vet}/slots', [Client\SlotController::class, 'getSlots'])->name('vets.slots');

        // Consultation Bookings
        Route::get('/bookings', [Client\BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/create', [Client\BookingController::class, 'create'])->name('bookings.create');
        Route::post('/bookings', [Client\BookingController::class, 'store'])->name('bookings.store');
        Route::get('/bookings/{consultation}', [Client\BookingController::class, 'show'])->name('bookings.show');
        Route::post('/bookings/{consultation}/cancel', [Client\BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::post('/bookings/{consultation}/accept-reschedule', [Client\BookingController::class, 'acceptReschedule'])->name('bookings.accept-reschedule');
        Route::post('/bookings/{consultation}/decline-reschedule', [Client\BookingController::class, 'declineReschedule'])->name('bookings.decline-reschedule');
    });

    // ----------------------------------------------------
    // VETERINARIAN ROUTES
    // ----------------------------------------------------
    Route::middleware('role:veterinarian')->prefix('vet')->name('vet.')->group(function () {
        Route::get('/dashboard', [Vet\DashboardController::class, 'index'])->name('dashboard');
        Route::post('/toggle-availability', [Vet\DashboardController::class, 'toggleAvailability'])->name('toggle-availability');

        // Schedule & Profile Settings
        Route::get('/schedule', [Vet\ScheduleController::class, 'index'])->name('schedule.index');
        Route::post('/schedule/profile', [Vet\ScheduleController::class, 'updateProfile'])->name('schedule.profile');
        Route::post('/schedule/availability', [Vet\ScheduleController::class, 'storeAvailability'])->name('schedule.availability');
        Route::delete('/schedule/availability/{availability}', [Vet\ScheduleController::class, 'deleteAvailability'])->name('schedule.availability.delete');

        // Booking Requests
        Route::get('/requests', [Vet\RequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/{consultation}', [Vet\RequestController::class, 'show'])->name('requests.show');
        Route::post('/requests/{consultation}/accept', [Vet\RequestController::class, 'accept'])->name('requests.accept');
        Route::post('/requests/{consultation}/suggest-reschedule', [Vet\RequestController::class, 'suggestReschedule'])->name('requests.suggest-reschedule');
        Route::post('/requests/{consultation}/decline', [Vet\RequestController::class, 'decline'])->name('requests.decline');
        Route::post('/requests/{consultation}/complete', [Vet\RequestController::class, 'markCompleted'])->name('requests.complete');

        // Clinical Notes / Prescriptions
        Route::get('/requests/{consultation}/records/create', [Vet\ClinicalNoteController::class, 'create'])->name('records.create');
        Route::post('/requests/{consultation}/records', [Vet\ClinicalNoteController::class, 'store'])->name('records.store');
    });

    // ----------------------------------------------------
    // ADMINISTRATOR ROUTES
    // ----------------------------------------------------
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

        // Vet Verification Management
        Route::get('/vets', [Admin\VetVerificationController::class, 'index'])->name('vets.index');
        Route::get('/vets/{vet}', [Admin\VetVerificationController::class, 'show'])->name('vets.show');
        Route::post('/vets/{vet}/approve', [Admin\VetVerificationController::class, 'approve'])->name('vets.approve');
        Route::post('/vets/{vet}/reject', [Admin\VetVerificationController::class, 'reject'])->name('vets.reject');
        Route::post('/vets/{vet}/suspend', [Admin\VetVerificationController::class, 'suspend'])->name('vets.suspend');
        Route::post('/vets/{vet}/reactivate', [Admin\VetVerificationController::class, 'reactivate'])->name('vets.reactivate');
        Route::post('/vets/{vet}/fees', [Admin\VetVerificationController::class, 'updateFees'])->name('vets.fees');

        // Pet Categories & Breeds
        Route::get('/categories', [Admin\PetCategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories/type', [Admin\PetCategoryController::class, 'storeType'])->name('categories.type.store');
        Route::post('/categories/breed', [Admin\PetCategoryController::class, 'storeBreed'])->name('categories.breed.store');
        Route::delete('/categories/breed/{breed}', [Admin\PetCategoryController::class, 'deleteBreed'])->name('categories.breed.delete');

        // User Management & Credits
        Route::get('/users', [Admin\UserController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/credits', [Admin\UserController::class, 'addCredits'])->name('users.credits');

        // Master Consultations List
        Route::get('/consultations', [Admin\ConsultationController::class, 'index'])->name('consultations.index');
        Route::get('/consultations/{consultation}', [Admin\ConsultationController::class, 'show'])->name('consultations.show');

        // System Settings
        Route::get('/settings', [Admin\SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    });

    // ----------------------------------------------------
    // LIVE CONSULTATION ROOMS (CHAT & WEBRTC VIDEO)
    // ----------------------------------------------------
    Route::get('/consultation/{consultation}/chat', [Consultation\ChatController::class, 'showRoom'])->name('consultation.chat');
    Route::get('/consultation/{consultation}/messages', [Consultation\ChatController::class, 'fetchMessages'])->name('consultation.messages');
    Route::post('/consultation/{consultation}/messages', [Consultation\ChatController::class, 'sendMessage'])->name('consultation.send-message');
    Route::post('/consultation/{consultation}/messages/read', [Consultation\ChatController::class, 'markAsRead'])->name('consultation.messages.read');
    Route::post('/consultation/{consultation}/chat/end', [Consultation\ChatController::class, 'endChat'])->name('consultation.chat.end');

    Route::get('/consultation/{consultation}/video', [Consultation\VideoController::class, 'showRoom'])->name('consultation.video');
    Route::post('/consultation/{consultation}/video/end', [Consultation\VideoController::class, 'endCall'])->name('consultation.video.end');
    Route::post('/consultation/{consultation}/sync-time', [Consultation\TimeSyncController::class, 'sync'])->name('consultation.sync-time');

    // Time Extensions
    Route::post('/consultation/{consultation}/doctor-add-time', [Consultation\TimeExtensionController::class, 'doctorAddTime'])->name('consultation.doctor-add-time');
    Route::post('/consultation/{consultation}/request-extension', [Consultation\TimeExtensionController::class, 'requestExtension'])->name('consultation.request-extension');
    Route::post('/consultation/{consultation}/extensions/{extension}/approve', [Consultation\TimeExtensionController::class, 'approveExtension'])->name('consultation.extensions.approve');
    Route::post('/consultation/{consultation}/extensions/{extension}/decline', [Consultation\TimeExtensionController::class, 'declineExtension'])->name('consultation.extensions.decline');
    Route::post('/consultation/{consultation}/extensions/{extension}/cancel', [Consultation\TimeExtensionController::class, 'cancelExtension'])->name('consultation.extensions.cancel');
});
