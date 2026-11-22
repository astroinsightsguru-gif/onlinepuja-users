@extends('frontend.astrologers.layout.master')
@section('content')

@php

    use Symfony\Component\HttpFoundation\Session\Session;
    use Illuminate\Support\Facades\Artisan;
    use App\Models\AstrologerModel\AstrologerStory;
    use Illuminate\Support\Facades\DB;
    use App\Models\AdminModel\SystemFlag;
    use Carbon\Carbon;
    
    if (astroauthcheck()) {
    $session = new Session();
    $token = $session->get('astrotoken');
    
    $chatrequest = DB::table('chatrequest')
    ->where('userId', astroauthcheck()['id'])
    ->get();
    
    $twentyFourHoursAgo = Carbon::now()->subHours(24);
    $stories = AstrologerStory::select(
    '*',
    DB::raw(
    '(Select Count(story_view_counts.id) as StoryViewCount from story_view_counts where storyId=astrologer_stories.id) as StoryViewCount',
    ),
    )
    ->where('created_at', '>=', $twentyFourHoursAgo)
    ->where('created_at', '<=', Carbon::now())
    ->where('astrologerId', astroauthcheck()['astrologerId'])
    ->orderBy('created_at', 'DESC')
    ->get();
    }
    $logo = DB::table('systemflag')->where('name', 'PartnerLogo')->select('value')->first();
    $appName = DB::table('systemflag')->where('name', 'AppName')->select('value')->first();

    $agoraAppIdValue = DB::table('systemflag')->where('name', 'AgoraAppId')->select('value')->first();

    $agorcertificateValue = DB::table('systemflag')->where('name', 'AgoraAppCertificate')->select('value')->first();

    $channel_name = 'Online PujaGuruLive_' . astroauthcheck()['astrologerId'] . '';

    $astrologerId = DB::table('liveastro')
    ->where('astrologerId', astroauthcheck()['astrologerId'])
    ->select('astrologerId')
    ->first();

    $getsystemflags = DB::table('systemflag')->get();
    $getsystemflag = $getsystemflags;
    $currency = SystemFlag::where('name', 'currencySymbol')->first();
    $OneSignalAppId = SystemFlag::where('name', 'OneSignalAppId')->first();
    $appId = $getsystemflag->where('name', 'firebaseappId')->first();
    $measurementId = $getsystemflag->where('name', 'firebasemeasurementId')->first();
    $messagingSenderId = $getsystemflag->where('name', 'firebasemessagingSenderId')->first();
    $storageBucket = $getsystemflag->where('name', 'firebasestorageBucket')->first();
    $projectId = $getsystemflag->where('name', 'firebaseprojectId')->first();
    $authDomain = $getsystemflag->where('name', 'firebaseauthDomain')->first();
    $databaseURL = $getsystemflag->where('name', 'firebasedatabaseURL')->first();
    $apiKey = $getsystemflag->where('name', 'firebaseapiKey')->first();
    $otplessAppId = $getsystemflag->where('name', 'otplessAppId')->first();

    $freekundali = $getsystemflags->where('name', 'FreeKundali')->first();
    $kundali_matching = $getsystemflags->where('name', 'KundaliMatching')->first();
    $panchang = $getsystemflags->where('name', 'TodayPanchang')->first();
    $blog = $getsystemflags->where('name', 'Blog')->first();
    $shop = $getsystemflags->where('name', 'Astromall')->first();
    $daily_horoscope = $getsystemflags->where('name', 'DailyHoroscope')->first();
    $Livesection = $getsystemflag->where('name', 'Livesection')->first();

    $astologerliveSection = DB::table('astrologers')
    ->where('id', astroauthcheck()['astrologerId'])
    ->select('live_sections')
    ->first();

@endphp

<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container mt-5 mb-5">
    <h3 class="mb-4 text-center fw-bold">📅 My Live Schedules</h3>

    @php
        $astrologerId = astroauthcheck()['astrologerId'];
        $astrologerLive = DB::table('liveastro')
            ->where('astrologerId', $astrologerId)
            ->first();
    @endphp

    @if($schedules->isEmpty())
        <div class="alert alert-info text-center shadow-sm p-4">
            <i class="fa-solid fa-calendar-xmark fa-2x mb-2"></i>
            <br>No schedules found.
        </div>
    @else
        <div class="row g-4">
            @foreach($schedules as $schedule)
                @php
                    $scheduleDateTime = \Carbon\Carbon::parse($schedule->schedule_live_date . ' ' . $schedule->schedule_live_time);
                    $now = \Carbon\Carbon::now();

                    // ✅ Go Live 10 minutes before, Expire 10 minutes after
                    $startBefore10Min = $scheduleDateTime->copy()->subMinutes(50);
                    $expireAfter10Min = $scheduleDateTime->copy()->addMinutes(50);
                    $canGoLive = $now->between($startBefore10Min, $expireAfter10Min);

                    if ($canGoLive) {
                        $statusText = "Active"; $statusClass = "bg-success";
                    } elseif ($now->lessThan($startBefore10Min)) {
                        $statusText = "Coming Soon"; $statusClass = "bg-warning";
                    } else {
                        $statusText = "Expired"; $statusClass = "bg-danger";
                    }
                @endphp

                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-lg schedule-card">
                        <div class="card-body p-2 d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="p-1 text-white rounded {{ $statusClass }}">{{ $statusText }}</span>
                                <i class="fa-solid fa-video text-primary fa-lg"></i>
                            </div>

                            <!-- <h5 class="card-title text-dark fw-semibold mb-3">{{ $schedule->type ?? 'General Session' }}</h5> -->
                            <div class="d-flex justify-content-between align-items-center px-2 py-1">
                                <div>
                                    <i class="fa-regular fa-calendar text-primary"></i>
                                    <span class="ms-2 fw-medium">
                                        {{ \Carbon\Carbon::parse($schedule->schedule_live_date)->format('d M, Y') }}
                                    </span>
                                </div>
                            
                                <div>
                                    <i class="fa-regular fa-clock text-primary"></i>
                                    <span class="ms-2 fw-medium">
                                        {{ \Carbon\Carbon::parse($schedule->schedule_live_time)->format('h:i A') }}
                                    </span>
                                </div>
                            </div>
                            <div class="mt-auto pt-3 text-end">
                                <button class="btn btn-outline-primary btn-sm px-3 editScheduleBtn"
                                    data-id="{{ $schedule->id }}"
                                    data-date="{{ $schedule->schedule_live_date }}"
                                    data-time="{{ $schedule->schedule_live_time }}"
                                    data-status="{{ $statusText }}">
                                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                </button>

                                <button class="btn btn-outline-danger btn-sm px-3 deleteScheduleBtn"
                                    data-id="{{ $schedule->id }}"
                                    data-status="{{ $statusText }}">
                                    <i class="fa-solid fa-trash me-1"></i> Delete
                                </button>

                                @if ($canGoLive)
                                    @if (empty($astrologerLive) || $astrologerLive->isActive == 0)
                                        <a href="javascript:void(0);"
                                           class="btn btn-success btn-sm px-3 goLiveBtn"
                                           data-schedule-id="{{ $schedule->id }}"
                                           data-channel="{{ $schedule->id . '_live_channel' }}">
                                            <i class="fa-solid fa-circle-play"></i> Go Live
                                        </a>
                                    @else
                                        <button class="btn btn-secondary btn-sm" disabled>
                                            Already Live
                                        </button>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{--  Edit Modal --}}
<div class="modal fade" id="editScheduleModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Schedule</h5>
        <button type="button" id="closeEditModal" aria-label="Close" style="background:none;border:none;font-size:1.4rem;line-height:1;color:#000;opacity:.6;cursor:pointer;">&times;</button>
      </div>

      <div class="modal-body p-4">
        <form id="editscheduleForm">
          @csrf
          <input type="hidden" name="id" id="editId">
          <div class="mb-3">
            <label class="form-label">Select Date</label>
            <input type="date" class="form-control" name="schedule_live_date" id="editDate" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Select Time</label>
            <input type="time" class="form-control" name="schedule_live_time" id="editTime" required>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary w-100">Update Schedule</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
.schedule-card {
    border-radius: 18px;
    transition: transform .2s ease-in-out, box-shadow .2s;
}
.schedule-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.12);
}
</style>

{{-- ✅ SweetAlert2 JS --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    let editId = null;

    // --- Open Edit Modal ---
    $('.editScheduleBtn').click(function() {
        const status = $(this).data('status');
        if (status === 'Expired') {
            Swal.fire('Expired', '❌ This schedule has expired. You cannot edit it.', 'warning');
            return;
        }
        editId = $(this).data('id');
        $('#editId').val(editId);
        $('#editDate').val($(this).data('date'));
        $('#editTime').val($(this).data('time'));
        $('#editScheduleModal').modal('show');
    });

    // --- Close Edit Modal ---
    $('#closeEditModal').click(function() {
        $('#editScheduleModal').modal('hide');
    });

    // --- Update Schedule ---
    $('#editscheduleForm').submit(function(e) {
        e.preventDefault();

        $.post("{{ url('/schedule/update') }}/" + editId, $(this).serialize())
            .done(function(res) {
                Swal.fire({
                    icon: 'success',
                    title: 'Schedule Updated',
                    text: 'Schedule updated successfully!',
                    timer: 1500,
                    showConfirmButton: false
                });
                setTimeout(() => location.reload(), 1500);
            })
            .fail(function() {
                Swal.fire('Error', '❌ Server error while updating schedule.', 'error');
            });
    });

    // --- Delete Schedule ---
    $('.deleteScheduleBtn').click(function() {
        const id = $(this).data('id');
        const status = $(this).data('status');

        if (status === 'Expired') {
            Swal.fire('Expired', '❌ This schedule has expired. You cannot delete it.', 'warning');
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you really want to delete this schedule?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post("{{ url('/schedule/delete') }}/" + id)
                    .done(function(res) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Schedule Deleted',
                            text: 'Schedule deleted successfully!',
                            timer: 1500,
                            showConfirmButton: false
                        });
                        setTimeout(() => location.reload(), 1500);
                    })
                    .fail(function() {
                        Swal.fire('Error', '❌ Server error while deleting schedule.', 'error');
                    });
            }
        });
    });
});
</script>


{{-- Go live JavaScript --}}
<script>
$(document).ready(function() {
    $('.goLiveBtn').click(function(e) {
        e.preventDefault();

        let scheduleId = $(this).data('schedule-id');
        let channelName = $(this).data('channel');
        let astrologerId = "{{ $astrologerId }}";

        Swal.fire({
            title: 'Do you want to start your scheduled live?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Go Live',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {

                // Step 1: Get RTC Token
                $.ajax({
                    url: '{{ route('api.generateRtcToken') }}',
                    type: 'POST',
                    data: {
                        appID: '<?= $agoraAppIdValue->value ?>',
                        appCertificate: '<?= $agorcertificateValue->value ?>',
                        channelName: channelName
                    },
                    success: function(response) {
                        let rtcToken = response.rtcToken;

                        // Step 2: Add Live Astrologer (linked to schedule)
                        $.ajax({
                            url: '{{ route('api.addLiveAstrologerWeb') }}',
                            type: 'POST',
                            data: {
                                astrologerId: astrologerId,
                                schedule_id: scheduleId,
                                channelName: channelName,
                                token: rtcToken
                            },
                            success: function(res) {
                                toastr.success('Live started successfully!');
                                window.location.href = "{{ route('front.LiveAstrologers') }}";

                                // Step 3: Notify users in background
                                setTimeout(() => {
                                    $.ajax({
                                        url: '{{ route('api.sendNotificationForliveAstro') }}',
                                        type: 'POST',
                                        data: {
                                            astrologerId: astrologerId,
                                            schedule_id: scheduleId
                                        },
                                        success: function() {
                                            console.log("Notification sent successfully");
                                        },
                                        error: function() {
                                            console.error("Notification failed");
                                        }
                                    });
                                }, 800);
                            },
                            error: function(xhr) {
                                toastr.error('Error while going live!');
                                console.log(xhr.responseText);
                            }
                        });
                    },
                    error: function(xhr) {
                        toastr.error('Token generation failed!');
                        console.log(xhr.responseText);
                    }
                });
            }
        });
    });
});
</script>

@endsection
