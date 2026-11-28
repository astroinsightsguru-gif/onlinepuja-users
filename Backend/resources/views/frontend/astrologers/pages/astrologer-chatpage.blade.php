@extends('frontend.astrologers.layout.master')

<style>
    .rounded {
        border: 1px solid #e4e5e6;
        border-radius: 10px !important;
    }

    .attachment-icon {
        cursor: pointer;
    }

    .attachment-icon i {
        padding: 10px;
        color: #aaa;
    }

    .attachment-icon:hover i {
        color: #333;
    }

    .chat-message-right {
        max-width: 70% !important;
    }

    .chat-message-left {
        max-width: 70% !important;
    }

    .chat-message-left {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .message-user-left-img {
        display: flex;
        gap: 10px;
        justify-content: start;
        align-items: center;
    }

    .message-user-left-img img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    .message-user-left-text {
        position: relative;
        padding: 15px 25px;
        background-color: #ffffff;
        border-radius: 15px;
        color: #000000;
        max-width: 250px;
    }

    .message-user-left-text::before {
        content: '';
        position: absolute;
        top: -26px;
        left: 15px;
        border-right: 15px solid transparent;
        border-top: 15px solid transparent;
        border-left: 0px solid transparent;
        border-bottom: 15px solid #ffffff;
    }

    .chat-messages.p-4 {
        background: #ececec;
    }

    .chat-message.chat-message-right {
        display: flex;
        flex-direction: column;
        align-items: end;
        gap: 15px;
    }

    .message-user-right {
        display: flex;
        flex-direction: column;
        align-items: end;
        gap: 15px;
    }

    .message-user-right-img {
        display: flex;
        gap: 10px;
        justify-content: end;
        align-items: center;
    }

    .message-user-right-img img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    .message-user-right-text {
        position: relative;
        padding: 15px 25px;
        background-color: #c5e2e8;
        border-radius: 15px;
        color: #000000;
        width: 250px;
    }

    .message-user-right-text::before {
        content: '';
        position: absolute;
        top: -26px;
        right: 15px;
        border-right: 0px solid transparent;
        border-top: 15px solid transparent;
        border-left: 15px solid transparent;
        border-bottom: 15px solid #c5e2e8;
    }

    .bg-msg {
        background: #207678c4 !important;
    }

    #test {
        font-size: 18px;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        border: none;
        background-color: #207678;
        color: #ffffff;
        cursor: pointer;
    }


    .btn-new {

        width: 10% !important;
    }

    #puja_popup .close {
    position: absolute;
    top: 5px;
    right: 5px;
    background: #fff !important;
    color: #000 !important;
    border: 1px solid #000 !important;
}
#customerExitWaitlist .close, #puja_popup .close {
    border-radius: 50%;
    width: 25px;
    height: 25px;
    display: flex
;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    padding-left: 0;
    z-index: 20;
    padding-bottom: 4px;
    opacity: 1;
    font-weight: 400;
}

    /* Show the profile details, timer, and controls in mobile view */
    @media (max-width: 767.98px) {
        .chat-messages {
            padding-bottom: 120px;
        }

        .card .py-2 {
            display: block !important;
        }

        #timerContainer {
            display: block !important;
            text-align: center;
        }

        .input-group {
            padding: 10px;
        }


        .rounded-circle {
            width: 30px;
            height: 30px;
        }

        / .flex-grow-1 strong {
            font-size: 14px;
        }

        .fa-paperclip,
        .fa-face-smile {
            font-size: 14px;
        }

        #remainingTime {
            font-size: 14px;
        }

        #endChat {
            font-size: 12px;
            padding: 5px 10px;
        }


        .btn-new {
            font-size: 12px !important;
            width: 42% !important;
        }
    }

    .chat-messages {
        height: 57vh !important;
        max-height: 57vh !important;
    }



    .tab-headbox {
        overflow-x: auto;
        /* Enable horizontal scrolling */
        overflow-y: hidden;
        /* Hide vertical overflow */
        white-space: nowrap;
        /* Prevent wrapping of tabs */
        -webkit-overflow-scrolling: touch;
        /* Smooth scrolling on touch devices */
        border-bottom: 1px solid #c2185b !important;
        /* Add border to the container */
    }

    #kundaliTab {
        display: inline-flex;
        /* Use inline-flex for horizontal layout */
        flex-wrap: nowrap;
        /* Prevent wrapping of tabs */
        padding: 0 15px;
        /* Add padding to the tabs container */
        margin-bottom: 0;
        /* Remove default margin */
        list-style: none;
        /* Remove list styling */
    }

    .nav-tabs {
        border-bottom: none !important;
        /* Remove default border */
    }

    .nav-tabs .nav-item {
        margin-bottom: -1px;
        /* Align tabs with the border */
        flex-shrink: 0;
        /* Prevent tabs from shrinking */
    }

    #kundaliTab li a.active {
        border-color: #c2185b #c2185b #FFFFFF !important;
        /* Active tab border color */
        color: #5E5E5E;
        /* Active tab text color */
    }

    #kundaliTab li a {
        font-weight: 600;
        /* Tab text font weight */
        color: #000000;
        /* Tab text color */
        white-space: nowrap;
        /* Prevent text wrapping */
        padding: 0.5rem 1rem !important;
        /* Tab padding */
        border: 1px solid transparent;
        /* Default tab border */
        border-top-left-radius: 0.25rem;
        /* Rounded corners */
        border-top-right-radius: 0.25rem;
        /* Rounded corners */
    }

    .nav-tabs .nav-item.show .nav-link,
    .nav-tabs .nav-link.active {
        color: #495057;
        /* Active tab text color */
        background-color: #fff;
        /* Active tab background color */
        border-color: #dee2e6 #dee2e6 #fff;
        /* Active tab border color */
    }

    .nav-tabs>li>a {
        background: white !important;
        /* Tab background color */
        border-bottom: 1px solid #c2185b !important;
        /* Tab bottom border */
    }

    @media (max-width: 768px) {
        #kundaliTab {
            display: inline-flex !important;
            /* Use inline-flex for wrapping behavior */
            flex-wrap: wrap !important;
            /* Allow tabs to wrap to the next line */
            overflow-x: visible !important;
            /* Disable horizontal scrolling */
        }

        .nav-tabs .nav-item {
            flex: 1 1 auto;
            /* Allow tabs to grow and shrink as needed */
            text-align: center;
            /* Center-align tab text */
        }

        #kundaliTab li a {
            padding: 0.5rem 0.75rem !important;
            /* Adjust padding for smaller screens */
            font-size: 14px;
            /* Reduce font size for better fit */
        }
    }

    @media (max-width: 576px) {
        svg {
            height: auto !important;
        }
    }
</style>

@section('content')
    @php
        use Symfony\Component\HttpFoundation\Session\Session;
    @endphp
    @if (astroauthcheck())
        @php

            $userId = request()->query('partnerId');
            $astrologerId = astroauthcheck()['astrologerId'];
            $chatId = request()->query('chatId');
            $astroId = astroauthcheck()['id'];

            $session = new Session();
            $token = $session->get('astrotoken');

            $keywords = DB::table('block-keywords')->get(['type', 'pattern']);


        @endphp
    @endif

    <div class="pt-1 pb-1 bg-red d-none d-md-block onlinepuja-breadcrumb">
        <div class="container">
            <div class="row afterLoginDisplay">
                <div class="col-md-12 d-flex align-items-center">

                    <span style="text-transform: capitalize; ">


                        <span class="text-white breadcrumbs">
                            <a href="{{ route('front.astrologerindex') }}" style="color:white;text-decoration:none">
                                <i class="fa fa-home font-18"></i>
                            </a>
                            <i class="fa fa-chevron-right"></i> <span class="breadcrumbtext">Chat</span>

                        </span>

                    </span>

                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->

    {{-- @if ($KundaliReport['planet']['status'] != 400 && $KundaliReport['planet']['status'] != 402) --}}
    <!-- Kundali Report Modal -->
    <div class="modal fade" id="kundaliModal" tabindex="-1" role="dialog" aria-labelledby="kundaliModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="kundaliModalLabel">Kundali Report</h5>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close" id="kundaliCloseBtn">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="kundaliContent">
                    <!-- Content will be loaded here via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="kundaliCloseFooterBtn">Close</button>
                </div>
            </div>
        </div>
    </div>
    {{-- @endif --}}

      <div class="modal fade rounded modalcenter" id="gift_popup" tabindex="-1" aria-labelledby="myModel_gift_popup"
        style="display: none;" aria-hidden="true">
        <div class="modal-dialog">
            <form id="giftForm">
                <div class="modal-content">
                    <button type="button" class="close" id="giftCloseBtn" data-bs-dismiss="modal">×</button>
                    <!-- Modal body -->
                    <div class="modal-body px-0">
                        <div class="position-relative  text-center w-100">
                            <h3 class="d-block font-weight-bold font-20" id="leave-expert-name">Recommend Products</h3>

                        </div>

                        <div class="bg-white text-center p-2">
                            <div id="loadGiftItems" class="loadGiftItems d-flex flex-wrap" style="height: 400px;">
                                @foreach ($astromallProduct as $product)
                                    <div class="loadGiftItem d-flex align-items-center justify-content-center"
                                        id="user-gift-{{ $product->id }}" style="height: 150px;width: 50%;">
                                        <a href="javascript:void(0)"
                                            onclick="copyReferralLink({{ $product->id }}, {{ $userId }}, {{ $astrologerId }}, '{{ url('product/' . $product->slug) }}')"
                                            style="width:100%;height:100%;max-width:100%;">
                                            <img src="/{{ $product->productImage }}" class="mt-1"
                                                style="width: 70px;height:70px;border-radius:15%">
                                            <p style="margin-bottom: 0;font-size:14px" class="gift-name text-wrap">
                                                {{ $product->name }}</p>
                                            {{ $currency->value }} {{ $product->amount }}
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    {{-- Puja MOdel --}}


 <div class="modal fade rounded modalcenter" id="puja_popup" tabindex="-1" aria-labelledby="myModel_puja_popup"
        style="display: none;" aria-hidden="true">
        <div class="modal-dialog">
            <form id="pujaForm">
                <div class="modal-content">
                    <button type="button" class="close" id="pujaCloseBtn" data-bs-dismiss="modal">×</button>
                    <!-- Modal body -->
                    <div class="modal-body px-0">
                        <div class="position-relative  text-center w-100">
                            <h3 class="d-block font-weight-bold font-20" id="leave-expert-name">Recommend Puja</h3>

                        </div>

                        <div class="bg-white text-center p-2">
                            <div id="loadPujaItems" class="loadGiftItems d-flex flex-wrap" style="height: 400px;">
                                @foreach ($pujalists as $puja)

                                <?php
                                $images = $puja->puja_images;
                                $firstImage = !empty($images) ? $images[0] : 'build/assets/images/person.png';

                                ?>
                                    <div class="loadGiftItem d-flex align-items-center justify-content-center"
                                        id="user-puja-{{ $puja->id }}" style="height: 150px;width: 50%;">
                                        <a href="javascript:void(0)"
                                            onclick="copyPujaReferralLink({{ $puja->id }}, {{ $userId }}, {{ $astrologerId }}, '{{ url('puja-details/' . $puja->slug) }}')"
                                            style="width:100%;height:100%;max-width:100%;">
                                            <img src="{{ asset($firstImage) }}" class="mt-1"
                                                style="width: 70px;height:70px;border-radius:15%">
                                            <p style="margin-bottom: 0;font-size:14px" class="gift-name text-wrap">
                                                {{ \Illuminate\Support\Str::limit($puja->puja_title, 58, '...') }}</p>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>

    {{-- End --}}

    <main class="content">
        <div class="container p-0">

            <h1 class="h3 mb-3 mt-4 ml-4">Chat</h1>

            <div class="card ">
                <div class="row g-0">

                    <div class="col-12 col-lg-12 col-xl-12">

                        <div class="py-2 px-4 border-bottom d-none d-lg-block">
                            <div class="d-flex align-items-center py-1">
                                <div class="position-relative">
                                    @if (isset($getUser['recordList'][0]['profile']) && $getUser['recordList'][0]['profile'])
                                        <img src="{{ asset($getUser['recordList'][0]['profile']) }}" class="rounded-circle mr-1"
                                            alt="{{ $getUser['recordList'][0]['name'] ?? 'User' }}" width="40" height="40">
                                    @else
                                        <img src="{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/images/user-img-new.png') }}"
                                            class="rounded-circle mr-1" alt="User" width="40" height="40">
                                    @endif
                                </div>

                                <div class="flex-grow-1 pl-3">
                                    <strong>{{ $getUser['recordList'][0]['name'] ?? 'User' }}
                                    </strong>



                                    {{-- <div class="text-muted small"><i>100 seconds</i></div> --}}
                                </div>


                                <button type="button" class="btn btn-report mr-3" id="kundaliButton">
                                    Kundali
                                </button>
                                <div id="timerContainer">
                                    <div class="text-muted small">
                                        <span id="statusText">Waiting to Join</span>
                                        <span id="remainingTime" class="color-red" style="display: none;"></span>
                                        <button class="btn view-more" id="endChat">End</button>
                                    </div>
                                </div>


                            </div>
                        </div>


                        <div class="position-relative">
                            <div class="chat-messages p-4">

                            </div>

                            <div class="flex-grow-0 py-3 px-4 border-top">

                                <div class="input-group">
                                    <a href="javascript:void(0)" data-toggle="modal"
                                        @if (!astroauthcheck()) data-target="#loginSignUp" @else data-target="#puja_popup" @endif
                                        class="btn btn-raised  waves-effect waves-light ml-2  d-flex align-items-center justify-content-center"
                                        id="send_gift">
                                        <i class="fa-solid fa-hands-praying"></i>
                                    </a>
                                    <a href="javascript:void(0)" data-toggle="modal"
                                        @if (!astroauthcheck()) data-target="#loginSignUp" @else data-target="#gift_popup" @endif
                                        class="btn btn-raised  waves-effect waves-light ml-2  d-flex align-items-center justify-content-center"
                                        id="send_gift">
                                        <img src="{{ asset('public/frontend/homeimage/shareproduct.png') }}"
                                            style="height: 25px" alt="">
                                    </a>
                                    <div class="input-group-append">
                                        <input type="file" id="fileInput_chat" class="d-none">
                                        <label for="fileInput_chat" class="attachment-icon">
                                            <!-- Hidden file input -->
                                            <i class="fas fa-paperclip"></i> <!-- Attachment icon -->
                                        </label>
                                    </div>
                                    <input type="text" id="fileDisplay" class="form-control"
                                        placeholder="Enter your message..">
                                    <button class="btn btn-chat" id="sendButton">Send</button>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </main>
@endsection


@section('scripts')
    {{-- <script>
    document.getElementById("kundaliButton").addEventListener("click", function() {
        var status = {{ $KundaliReport['planet']['status'] }};

        if (status == 400 || status == 402) {
            alert("No Kundali found");
        } else {
            $("#kundaliModal").modal("show"); // Show modal if status is valid
        }
    });
</script> --}}
    <script>
        function copyReferralLink(productId, userId, astrologerId, referralLink) {
            productId = productId.toString(); // Convert productId to a string if necessary
            userId = userId.toString(); // Convert userId to a string if necessary
            astrologerId = astrologerId.toString(); // Convert astrologerId to a string if necessary

            toastr.info('Recommending product...');

            navigator.clipboard.writeText(referralLink).then(function() {

                $.ajax({
                    url: "{{ route('api.addProductRecommend') }}", // Adjust the URL as necessary
                    method: 'POST',
                    data: {
                        productId: productId,
                        userId: userId,
                        astrologerId: astrologerId
                    },
                    success: function(response) {
                        sendMessage(astrologerId, userId, referralLink, false, '');
                        // Close modal after successful recommendation
                        $('#gift_popup').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error adding product recommendation:', error);
                        // Still close modal even if there's an error
                        $('#gift_popup').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                    }
                });

            }, function(err) {
                // Error occurred in copying
                console.error("Failed to copy the referral link: ", err);
                // Close modal even if copy failed
                $('#gift_popup').modal('hide');
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
            });
        }


function copyPujaReferralLink(pujaId, userId, astrologerId, referralLink) {
            pujaId = pujaId.toString(); // Convert productId to a string if necessary
            userId = userId.toString(); // Convert userId to a string if necessary
            astrologerId = astrologerId.toString(); // Convert astrologerId to a string if necessary

            toastr.info('Recommending puja...');

            navigator.clipboard.writeText(referralLink).then(function() {

                $.ajax({
                    url: "{{ route('api.addPujaRecommend') }}", // Adjust the URL as necessary
                    method: 'POST',
                    data: {
                        puja_id: pujaId,
                        userId: userId,
                        astrologerId: astrologerId
                    },
                    success: function(response) {
                        sendMessage(astrologerId, userId, referralLink, false, '');
                        // Close modal after successful recommendation
                        $('#puja_popup').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                    },
                    error: function(xhr, status, error) {
                        console.error('Error adding puja recommendation:', error);
                        // Still close modal even if there's an error
                        $('#puja_popup').modal('hide');
                        $('.modal-backdrop').remove();
                        $('body').removeClass('modal-open');
                    }
                });

            }, function(err) {
                // Error occurred in copying
                console.error("Failed to copy the referral link: ", err);
                // Close modal even if copy failed
                $('#puja_popup').modal('hide');
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
            });
        }


        document.getElementById('fileInput_chat').addEventListener('change', function() {
            const fileInput = this;
            const fileName = fileInput.files[0] ? fileInput.files[0].name : 'No file chosen';
            document.getElementById('fileDisplay').value = fileName;
        });


        var userId = "{{ $userId }}";
        var astrologerId = "{{ $astrologerId }}";
        var astroId = "{{ $astroId }}";


        var patterns = {!! json_encode($keywords) !!};
        console.log("Raw Keywords:", patterns); // Check the raw data in the console

        // Function to decode and parse patterns
        function decodeAndParsePatterns(patterns) {
            // Ensure that the patterns is an array of objects and process accordingly
            try {
                patterns.forEach(item => {
                    if (item.type === 'offensive-word' && item.pattern) {
                        try {
                            console.error('item.pattern-1:', item.pattern);

                            // If the pattern is a stringified array, parse it into an actual array
                            item.pattern = JSON.parse(item.pattern);
                            console.error('item.pattern:', item.pattern);
                        } catch (e) {
                            console.error('Error parsing offensive word pattern:', e);
                            item.pattern = []; // Set it to an empty array if parsing fails
                        }
                    }
                });

                return patterns;
            } catch (error) {
                console.error("Error in decodeAndParsePatterns:", error);
                return []; // Return an empty array if an error occurs
            }
        }

        // Decode and parse the patterns
        const parsedPatterns = decodeAndParsePatterns(patterns);
        console.log("Parsed Patterns:", parsedPatterns); // Check the final parsed patterns

        let sensitiveWordsCount = 0;
        var defaulter = 0;

        // Function to mask sensitive words
        function maskSensitiveWord(word, type) {
            if (type === 'email') {
                const parts = word.split('@');
                const localPart = parts[0];
                const domainPart = parts[1];

                const maskedLocalPart = localPart.length > 2 ?
                    localPart.charAt(0) + '*'.repeat(localPart.length - 2) + localPart.slice(-1) :
                    localPart; // If it's too short, don't mask
                return maskedLocalPart + '@' + domainPart;
            }

            if (type === 'phone') {
                return word.length > 2 ?
                    word.charAt(0) + '*'.repeat(word.length - 2) + word.charAt(word.length - 1) :
                    word; // If it's too short, don't mask
            }

            if (type === 'url') {
                // Mask URL (e.g., https://www.******.com)
                const urlParts = word.split('://');
                return urlParts[0] + '://*****.com';
            }

            if (type === 'offensive-word') {
                // Mask offensive words
                const firstChar = word.charAt(0);
                const lastChar = word.charAt(word.length - 1);
                const masked = firstChar + '*'.repeat(word.length - 2) + lastChar;
                return masked;
            }

            return word; // Return word unchanged if no pattern matches
        }

        // Function to process the message and mask sensitive words
        function processMessage(message) {
            let maskedMessage = message;
            sensitiveWordsCount = 0; // Reset the count at the start of each message processing

            // Check and mask all sensitive words based on patterns
            parsedPatterns.forEach((pattern) => {
                // Handle 'phone' and 'email' types when pattern is "true"
                if (pattern.pattern === 'true') {
                    // Phone regex pattern
                    if (pattern.type === 'phone') {
                        const phoneRegex = /\b(\+91|91)?\d{10}\b/g;
                        const matches = message.match(phoneRegex);
                        if (matches) {
                            defaulter = 1;
                            sensitiveWordsCount += matches.length;
                            matches.forEach((match) => {
                                const maskedWord = maskSensitiveWord(match, 'phone');
                                maskedMessage = maskedMessage.replace(match, maskedWord);
                            });
                        }
                    }

                    // Email regex pattern
                    if (pattern.type === 'email') {
                        const emailRegex = /\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}\b/g;
                        const matches = message.match(emailRegex);
                        if (matches) {
                            defaulter = 1;
                            sensitiveWordsCount += matches.length;
                            matches.forEach((match) => {
                                const maskedWord = maskSensitiveWord(match, 'email');
                                maskedMessage = maskedMessage.replace(match, maskedWord);
                            });
                        }
                    }
                }

                // Offensive word pattern, if it is an array
                if (pattern.type === 'offensive-word' && Array.isArray(pattern.pattern)) {
                    const offensiveWordsRegex = new RegExp(`\\b(${pattern.pattern.join('|')})\\b`, 'gi');
                    const matches = message.match(offensiveWordsRegex);
                    if (matches) {
                        defaulter = 1;
                        sensitiveWordsCount += matches.length;
                        matches.forEach((match) => {
                            const maskedWord = maskSensitiveWord(match, 'offensive-word');
                            maskedMessage = maskedMessage.replace(match, maskedWord);
                        });
                    }
                }
            });

            return maskedMessage;
        }
        // ----------------------------------------end-defaulter---------------------------



        const firestore = firebase.firestore();

        // Initialize Firebase updatechat document with initial duration
        const db = firebase.firestore();
        db.collection('updatechat').doc("{{ $chatId }}").set({
            duration: {{ $chatrequest->chat_duration }}
        }, { merge: true });

        // Function to send a message
        function sendMessage(senderId, receiverId, message, isEndMessage, attachementPath) {
            const chatRef = firestore.collection('chats').doc(`${senderId}_${receiverId}`).collection('userschat').doc(
                senderId).collection('messages');
            const timestamp = new Date();
            // Generate a unique ID for the message
            const messageId = chatRef.doc().id;

            chatRef.doc(messageId).set({
                    id: null,
                    createdAt: timestamp,
                    invitationAcceptDecline: null,
                    isDelete: false,
                    isEndMessage: isEndMessage,
                    isRead: false,
                    messageId: messageId,
                    reqAcceptDecline: null,
                    status: null,
                    updatedAt: timestamp,
                    url: null,
                    userId1: senderId,
                    userId2: receiverId,
                    message: message,
                    attachementPath: attachementPath, // Pass attachementPath to the message object
                })
                .then(() => {
                    // console.log("Message sent with ID: ", messageId);
                })
                .catch((error) => {
                    console.error("Error sending message: ", error);
                });
        }


        $(document).on('keydown', '#fileDisplay', function(event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                $('#sendButton').click();
            }
        });

        $(document).ready(function() {
            $(document).on('click', '.btn-chat', function() {
                // ---------------------store-defaulter-message------------------------------------------------
                const originalMessage = $('#fileDisplay').val(); // Get the original message
                const processedMessage = processMessage(originalMessage); // Process to mask sensitive words
                $('#fileDisplay').val(processedMessage);

                if (defaulter == 1) {

                    $.ajax({
                        url: '/store-defaulter-message', // Replace with your actual backend URL
                        type: 'POST',
                        data: {
                            message: originalMessage,
                            userId: astroId,
                            type: 'astrologer',
                            sender_type: 'astrologer',
                            sender_id: astroId,
                            receiver_type: 'user',
                            receiver_id: userId,

                            _token: '{{ csrf_token() }}' // CSRF token for Laravel (or your backend framework)
                        },
                        success: function(response) {
                            defaulter = 0;
                            toastr.warning(response.message);
                        },
                        error: function(xhr, status, error) {
                            alert('Error sending message: ' + error);
                        }
                    });

                } else {
                    defaulter == 0;
                }
                // --------------------end-defaulter-message-store----------------------------------------------

                const messageInput = $(this).closest('.input-group').find('.form-control');
                // console.log('Input field value:', messageInput.val());

                const message = messageInput.val().trim();
                // console.log('Trimmed message:', message);

                const fileInput = $(this).closest('.input-group').find('#fileInput_chat')[0];
                console.log(fileInput);
                const file = fileInput.files[0]; // Get the selected file

                console.log(file);

                if (message !== '' || file) { // Check if message or file is not empty
                    console.log('Message or file is not empty');

                    // Check if file is present, if so, upload it to Firebase Storage
                    if (file) {
                        const storageRef = firebase.storage().ref();
                        const fileName = `${astrologerId}_${userId}/${file.name}`;
                        const fileRef = storageRef.child(fileName);
                        console.log(fileRef);

                        fileRef.put(file).then((snapshot) => {
                            console.log('File uploaded successfully');
                            snapshot.ref.getDownloadURL().then((downloadURL) => {
                                // console.log('File download URL:', downloadURL);
                                // Send the message as null when a file is attached
                                sendMessage(astrologerId, userId, null, false,
                                    downloadURL); // Pass download URL to sendMessage
                                messageInput.val('');
                                fileInput.value = ''; // Clear file input after sending
                            });
                        }).catch((error) => {
                            console.error('Error uploading file:', error);
                        });

                    } else {
                        // If no file, simply send the message
                        sendMessage(astrologerId, userId, message, false,
                            ''); // Pass empty string as attachment path
                        messageInput.val('');
                    }
                } else {
                    toastr.error('Message and file are empty');
                }
            });
        });

        function fetchAndRenderMessages(receiverId, senderId) {
            const senderChatRef = firestore.collection('chats').doc(`${receiverId}_${senderId}`).collection('userschat')
                .doc(receiverId).collection('messages');

            senderChatRef.orderBy('createdAt', 'asc').onSnapshot(snapshot => {
                snapshot.docChanges().forEach(change => {
                    if (change.type === 'added') {
                        const message = change.doc.data();
                        renderMessage(message, receiverId);
                        scrollToBottom();
                    }
                });
            });
        }



        function renderMessage(message, receiverId) {
            const chatMessagesContainer = document.querySelector('.chat-messages');
            const isScrolledToBottom = chatMessagesContainer.scrollHeight - chatMessagesContainer.clientHeight <=
                chatMessagesContainer.scrollTop + 1;

            const messageElement = document.createElement('div');
            messageElement.classList.add('chat-message');

            const timestamp = message.createdAt.toDate();
            const formattedTime = timestamp.toLocaleTimeString('en-US', {
                hour: 'numeric',
                minute: '2-digit'
            });



            var newupdateTime = new Date("{{ $chatrequest->updated_at }}").toLocaleString('en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
            var newtimestamp = timestamp.toLocaleString('en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
            @if (astroauthcheck() && !empty(astroauthcheck()->profile))
                var astroprofile = "{{ asset(astroauthcheck()->profile) }}";
            @elseif(astroauthcheck() && !empty(astroauthcheck()->profileImage))
                var astroprofile = "{{ asset(astroauthcheck()->profileImage) }}";
            @else
                var astroprofile =
                    "{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/images/user-img-new.png') }}";
            @endif

            @if (isset($getUser['recordList'][0]['profile']) && $getUser['recordList'][0]['profile'])
                var userprofile = "{{ asset($getUser['recordList'][0]['profile']) }}";
            @else
                var userprofile =
                    "{{ asset('public/frontend/onlinepujacdn/dashaspeaks/web/content/images/user-img-new.png') }}";
            @endif

            if (message.isEndMessage == true) {
                messageElement.innerHTML = `
                    <div class="chat-message chat-message-center d-flex m-3" style="justify-content: center;">
                        <div class="color-red bg-pink rounded-pill border py-1 px-3 mr-3 mb-2 text-center" style="width: 60%;">
                            ${convertToLink(message.message)}
                        </div>
                    </div>`;
            } else if (message.userId1 != receiverId) {
                // Message sent by the receiver, render on the left side
                messageElement.classList.add('chat-message-left');
                messageElement.innerHTML = `
                    <div class="message-user-left-img mt-3">
                         <img src="${userprofile}" class="rounded-circle mr-1" alt="Sender" width="40" height="40">
                            <strong>{{ $getUser['recordList'][0]['name'] ?: 'User' }}</strong>
                            <small>${formattedTime}</small>
                    </div>
                    <div class="message-user-left-text">
                        ${message.attachementPath ? renderAttachment(message.attachementPath) : `<strong>${convertToLink(message.message)}</strong>`}
                    </div>`;
            } else {
                // Message sent by the sender, render on the right side
                messageElement.classList.add('chat-message-right');
                messageElement.innerHTML = `
                    <div class="message-user-right-img mt-3">
                        <strong>You</strong>
                         <small>${formattedTime}</small>
                        <img src="${astroprofile}" class="rounded-circle mr-1" alt="You" width="40" height="40">
                    </div>
                    <div class="message-user-right-text">
                        ${message.attachementPath ? renderAttachment(message.attachementPath) : `<strong>${convertToLink(message.message)}</strong>`}
                    </div>`;
            }





            chatMessagesContainer.appendChild(messageElement);

            if (isScrolledToBottom) {
                chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
            }
        }

        function scrollToBottom() {
            const chatMessagesContainer = document.querySelector('.chat-messages');
            chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
        }

        function convertToLink(message) {
            const urlPattern = /(https?:\/\/[^\s]+)/g;
            return message.replace(urlPattern, '<a href="$1" target="_blank">$1</a>');
        }

        function renderAttachment(attachementPath) {
            if (!attachementPath) return ''; // No attachment provided

            // Remove query parameters from the URL
            const filePathWithoutParams = attachementPath.split('?')[0];

            // Extract the file extension
            const fileExtension = filePathWithoutParams.split('.').pop().toLowerCase();



            // List of image file extensions
            const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

            // If the file is an image, render it as an image
            if (imageExtensions.includes(fileExtension)) {
                return `<img src="${attachementPath}" style="max-height:250px;" alt="Attachment" class="img-fluid">`;
            }

            // Define icons for different file types
            const fileIcons = {
                pdf: 'fa-file-pdf',
                xlsx: 'fa-file-excel',
                xls: 'fa-file-excel',
                docx: 'fa-file-word',
                doc: 'fa-file-word',
                txt: 'fa-file-alt',
                csv: 'fa-file-csv',
                zip: 'fa-file-archive',
                // Add more file types as needed
            };

            // Default icon for unknown file types
            const defaultIcon = 'fa-file';

            // Get the appropriate icon for the file type
            const fileIcon = fileIcons[fileExtension] || defaultIcon;

            // Render the file attachment with an icon and download link
            return `
            <div class="file-attachment" style="cursor: pointer;" onclick="downloadFile('${attachementPath}')">
                <i class="fas ${fileIcon}"></i>
                <p class="mt-2">Attachment</p>
            </div>`;
        }

        // Function to check if a URL points to an image
        function isImage(url) {
            return /\.(jpeg|jpg|gif|png)$/i.test(url);
        }


        document.addEventListener('DOMContentLoaded', function() {
            fetchAndRenderMessages(astrologerId, userId);
        });

        function downloadFile(url) {
            window.open(url, '_blank');

        }
    </script>

    <script>
        $(document).ready(function() {
            let timer;
            let timerStarted = false;

            var updateTime = new Date("{{ $chatrequest->updated_at }}").getTime();
            var chatDuration = {{ $chatrequest->chat_duration }};
            // var currentTime = new Date().getTime();
            // var elapsedTime = Math.floor((currentTime - updateTime) / 1000);
            // var remainingTime = chatDuration - elapsedTime;
            let currentTime = remainingTime = elapsedTime = '';

            $.get("{{ route('front.getDateTime') }}", function(response) {
                // Assuming the response contains the server time in 'Y-m-d H:i:s' format
                currentTime = new Date(response).getTime();

                // Calculate elapsed time and remaining time
                let elapsedTime = Math.floor((currentTime - updateTime) / 1000);
                remainingTime = chatDuration - elapsedTime;

                // Ensure remainingTime is not negative
                if (remainingTime < 0) {
                    remainingTime = 0;
                }



            }).fail(function() {
                console.error("Error fetching server time");
            });

            function updateTimer() {
                var minutes = Math.floor(remainingTime / 60);
                var seconds = remainingTime % 60;
                var formattedTime = (minutes < 10 ? '0' : '') + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                document.getElementById('remainingTime').innerHTML = formattedTime + '&nbsp;&nbsp;&nbsp;';
            }

            function startTimer() {
                setupFirebaseListener();
                if (timerStarted) return;
                timerStarted = true;

                $('#statusText').text('Remaining :');
                $('#remainingTime').show();

                timer = setInterval(function() {
                    remainingTime--;
                    if (remainingTime < 0) {
                        remainingTime = 0;
                        clearInterval(timer);
                        sendMessage(astrologerId, userId, "{{ astroauthcheck()['name'] }} -> Chat Ended",
                            true, null);
                        setTimeout(function() {
                            window.location.href = "{{ route('front.astrologerindex') }}";
                        }, 2000);
                    }
                    updateTimer();
                    // fetchCurrentChatDuration(); // Periodically update chat duration
                }, 1000);
                // Separate interval for fetchCurrentChatDuration every 3 seconds

            }

            function setupFirebaseListener() {
            const chatId = '{{ $chatId }}'; // Your Laravel chat ID
            const db = firebase.firestore();

            // Listen to the specific document in 'updatechat' collection
            db.collection('updatechat').doc(chatId)
                .onSnapshot((doc) => {
                    if (doc.exists) {
                        const firebaseData = doc.data();
                        const newDuration = firebaseData.duration;
                        console.log("Firebase update received on astrologer side:", newDuration);
                        const previousDuration = chatDuration;

                        // Update chatDuration
                        chatDuration = newDuration;

                        // Adjust remaining time only if duration increased
                        if (chatDuration > previousDuration) {
                            const additionalTime = chatDuration - previousDuration;
                            remainingTime += additionalTime;
                            console.log("Added additional time from Firebase:", additionalTime);

                            // Update UI immediately
                            updateTimer();
                        }
                    }
                }, (error) => {
                    console.error("Firebase listener error:", error);
                });
        }


            function checkChatStatus() {
                $.ajax({
                    url: "{{ route('front.chatStatus') }}",
                    method: 'GET',
                    data: {
                        chatId: '{{ $chatId }}'
                    },
                    success: function(response) {
                        if (response.chatStatus === 'Confirmed' && remainingTime > 0 && !timerStarted) {
                            startTimer();
                            console.log(response);
                        } else if (response.chatStatus === 'Completed') {
                            clearInterval(timer);
                            timerStarted = false;

                            $('#statusText').text('Remaining :');
                            $('#remainingTime').text('00:00');
                            $('#remainingTime').show();

                            setTimeout(function() {
                                window.location.href = "{{ route('front.astrologerindex') }}";
                            }, 2000);
                        } else if (response.chatStatus !== 'Confirmed') {
                            $('#statusText').text('Waiting to Join');
                            $('#remainingTime').hide();
                            clearInterval(timer);
                            timerStarted = false;
                        }
                    }
                });
            }

            setInterval(checkChatStatus, 3000); // Check every 3 seconds
            updateTimer();
        });

        $('#endChat').click(function(e) {

            $.ajax({
                url: "{{ route('api.addChatStatus') }}",
                type: 'POST',
                data: {
                    status: 'Online',
                    token: "{{ $token }}",
                    astrologerId: "{{ $astrologerId }}"
                },
                success: function(response) {
                    console.log('success');
                },
                error: function(xhr, status, error) {
                    console.error('Error updating chat status:', error);
                }
            });

            $.ajax({
                url: "{{ route('api.addCallStatus') }}",
                type: 'POST',
                data: {
                    status: 'Online',
                    token: "{{ $token }}",
                    astrologerId: "{{ $astrologerId }}"
                },
                success: function(response) {
                    console.log('success');
                },
                error: function(xhr, status, error) {
                    console.error('Error updating chat status:', error);
                }
            });


            window.location.href = "{{ route('front.astrologerindex') }}";
            sendMessage(astrologerId, userId, "{{ astroauthcheck()['name'] }} -> Chat Ended", true, null);
        });



        $(document).ready(function() {
            $('#kundaliButton').click(function() {
                var userId = "{{ $userId }}"; // Fetch user ID from PHP

                // Show loading text
                $('#kundaliButton').text('Loading...');

                // Call the API
                $.ajax({
                    url: "{{ url('/api/kundali/getKundaliReport') }}",
                    type: "POST",
                    data: {
                        userId: userId
                    },
                    headers: {
                        "X-CSRF-TOKEN": "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        //console.log(response); // Debugging

                        if (!response || response.planet.status == 400 || response.planet
                            .status == 402) {
                             $('#kundaliContent').html('<h3 class="text-center mt-5 mb-5">No Kundali Found</h3>');
                             $('#kundaliModal').modal('show');
                             $('#kundaliButton').text('Kundali');
                            return;
                        }

                        // Populate modal content dynamically
                        var html = generateKundaliReportHTML(response);
                        $('#kundaliContent').html(html);

                        // ✅ Open modal only after successful API response
                        $('#kundaliModal').modal('show');
                        $('#kundaliButton').text('Kundali');
                    },
                    error: function() {
                        $('#kundaliContent').html('<p>Error fetching Kundali report.</p>');
                        $('#kundaliModal').modal('show');
                        $('#kundaliButton').text('Kundali');
                    }
                });
            });

            // ✅ Explicit close handlers for Kundali modal
            $('#kundaliCloseBtn, #kundaliCloseFooterBtn').click(function() {
                $('#kundaliModal').modal('hide');
            });

            // ✅ Also handle backdrop click
            $('#kundaliModal').on('click', function(e) {
                if ($(e.target).hasClass('modal')) {
                    $('#kundaliModal').modal('hide');
                }
            });

            // ✅ Explicit close handlers for Gift modal
            $('#giftCloseBtn').click(function() {
                $('#gift_popup').modal('hide');
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
            });

            // ✅ Also handle backdrop click for Gift modal
            $('#gift_popup').on('click', function(e) {
                if ($(e.target).hasClass('modal')) {
                    $('#gift_popup').modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                }
            });

            // ✅ Explicit close handlers for Puja modal
            $('#pujaCloseBtn').click(function() {
                $('#puja_popup').modal('hide');
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
            });

            // ✅ Also handle backdrop click for Puja modal
            $('#puja_popup').on('click', function(e) {
                if ($(e.target).hasClass('modal')) {
                    $('#puja_popup').modal('hide');
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                }
            });

            function generateKundaliReportHTML(response) {
                var html = `
            <ul class="nav nav-tabs" id="kundaliTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="basic-tab" data-toggle="tab" href="#basic" role="tab"
                        aria-controls="basic" aria-selected="true">Basic Details</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="planetaryposition-tab" data-toggle="tab" href="#planetaryposition" role="tab"
                        aria-controls="planetaryposition" aria-selected="false">Planetary Position</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="predictions-tab" data-toggle="tab" href="#predictions" role="tab"
                        aria-controls="predictions" aria-selected="false">Predictions</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="shodashvarga-tab" data-toggle="tab" href="#shodashvarga" role="tab"
                        aria-controls="shodashvarga" aria-selected="false">Shodashvarga</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="ashtakvarga-tab" data-toggle="tab" href="#ashtakvarga" role="tab"
                        aria-controls="ashtakvarga" aria-selected="false">Ashtakvarga</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="mahadasha-tab" data-toggle="tab" href="#mahadasha" role="tab"
                        aria-controls="mahadasha" aria-selected="false">Mahadasha</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="yogini-tab" data-toggle="tab" href="#yogini" role="tab"
                        aria-controls="yogini" aria-selected="false">Yogini Dasha</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="dosha-tab" data-toggle="tab" href="#dosha" role="tab"
                        aria-controls="dosha" aria-selected="false">Dosha</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="report-tab" data-toggle="tab" href="#report" role="tab"
                        aria-controls="report" aria-selected="false">Report</a>
                </li>
            </ul>

            <div class="tab-content" id="kundaliTabContent">
                <!-- Basic Details Tab -->
                <div class="tab-pane fade show active" id="basic" role="tabpanel" aria-labelledby="basic-tab">
                    ${generateBasicDetailsHTML(response)}
                </div>

                <!-- Planetary Position Tab -->
                <div class="tab-pane fade" id="planetaryposition" role="tabpanel" aria-labelledby="planetaryposition-tab">
                    ${generatePlanetaryPositionHTML(response)}
                </div>

                <!-- Predictions Tab -->
                <div class="tab-pane fade" id="predictions" role="tabpanel" aria-labelledby="predictions-tab">
                    ${generatePredictionsHTML(response)}
                </div>

                <!-- Shodashvarga Tab -->
                <div class="tab-pane fade" id="shodashvarga" role="tabpanel" aria-labelledby="shodashvarga-tab">
                    ${generateShodashvargaHTML(response)}
                </div>

                <!-- Ashtakvarga Tab -->
                <div class="tab-pane fade" id="ashtakvarga" role="tabpanel" aria-labelledby="ashtakvarga-tab">
                    ${generateAshtakvargaHTML(response)}
                </div>

                <!-- Mahadasha Tab -->
                <div class="tab-pane fade" id="mahadasha" role="tabpanel" aria-labelledby="mahadasha-tab">
                    ${generateMahadashaHTML(response)}
                </div>

                <!-- Yogini Dasha Tab -->
                <div class="tab-pane fade" id="yogini" role="tabpanel" aria-labelledby="yogini-tab">
                    ${generateYoginiDashaHTML(response)}
                </div>

                <!-- Dosha Tab -->
                <div class="tab-pane fade" id="dosha" role="tabpanel" aria-labelledby="dosha-tab">
                    ${generateDoshaHTML(response)}
                </div>

                <!-- Report Tab -->
                <div class="tab-pane fade" id="report" role="tabpanel" aria-labelledby="report-tab">
                    ${generateReportHTML(response)}
                </div>
            </div>`;

                return html;
            }

            function generateBasicDetailsHTML(response) {
                return `
            <div class="row py-3">
                <div class="col-sm-12 mt-4">
                    <div class="table-responsive table-theme shadow-pink p-3">
                        <table class="table table-bordered border-pink font-14 mb-0">
                            <tbody>
                                <tr><th class="cellhead"><b>Name</b></th><td>${response.recordList.name || 'N/A'}</td></tr>
                                <tr><th class="cellhead"><b>Birth Date</b></th><td>${response.recordList.birthDate || 'N/A'}</td></tr>
                                <tr><th class="cellhead"><b>Birth Time</b></th><td>${response.recordList.birthTime || 'N/A'}</td></tr>
                                <tr><th class="cellhead"><b>Birth Place</b></th><td>${response.recordList.birthPlace || 'N/A'}</td></tr>
                                <tr><th class="cellhead"><b>Latitude</b></th><td>${response.recordList.latitude || 'N/A'}</td></tr>
                                <tr><th class="cellhead"><b>Longitude</b></th><td>${response.recordList.longitude || 'N/A'}</td></tr>
                                <tr><th class="cellhead"><b>Timezone</b></th><td>${response.recordList.timezone || 'N/A'}</td></tr>
                                <tr><th class="cellhead"><b>Rasi</b></th><td>${response.planet.response.rasi || 'N/A'}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>`;
            }

            function generatePlanetaryPositionHTML(response) {
                if (response.planet.status === 400) {
                    return `<p class="text-center">No Record Found</p>`;
                }

                var filteredData = Object.keys(response.planet.response)
                    .filter(key => !isNaN(key)) // Filter numerical keys (0 to 9)
                    .map(key => response.planet.response[key]);

                var rows = filteredData.map(planet => `
        <tr>
            <td>${planet.full_name || 'N/A'}</td>
            <td>${planet.is_combust ? 'C' : ''}</td>
            <td>${planet.retro ? 'R' : ''}</td>
            <td>${planet.zodiac || 'N/A'}</td>
            <td>${planet.local_degree || 'N/A'}</td>
            <td>${planet.global_degree || 'N/A'}</td>
            <td>${planet.nakshatra || 'N/A'}</td>
            <td>${planet.nakshatra_pada || 'N/A'}</td>
        </tr>
    `).join('');

                return `
        <div class="row">
            <div class="col-12">
                <div class="table-responsive table-theme shadow-pink p-3">
                    <table class="table table-bordered border-pink font-14 mb-0">
                        <thead class="matchV_thead bg-pink color-red">
                            <tr>
                                <th class="cellhead">Planet</th>
                                <th class="cellhead">C</th>
                                <th class="cellhead">R</th>
                                <th class="cellhead">Rashi</th>
                                <th class="cellhead">Local Degree</th>
                                <th class="cellhead">Global Degree</th>
                                <th class="cellhead">Nakshatra</th>
                                <th class="cellhead">Pada</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </div>
        </div>`;
            }

            function generatePredictionsHTML(response) {
                if (response.personal.status === 400) {
                    return `<p class="text-center">No Record Found</p>`;
                }

                var predictions = response.personal.response.map((prediction, index) => {
                    var houseNumber = index + 1;
                    var houseWord = ['First', 'Second', 'Third', 'Fourth', 'Fifth', 'Sixth', 'Seventh',
                        'Eighth', 'Ninth', 'Tenth', 'Eleventh', 'Twelfth'
                    ][houseNumber - 1] || houseNumber;

                    return `
            <div class="panel panel-default mb-3">
                <div class="panel-heading">
                    <h3 class="panel-title mb-0">
                        <a class="accordion-toggle font-weight-semi d-block py-2 colorblack font-16" data-toggle="collapse" data-parent="#accordion" href="#accAbount_${index}">
                            ${houseWord} House
                        </a>
                    </h3>
                </div>
                <div id="accAbount_${index}" class="panel-collapse collapse ${index === 0 ? 'show' : ''}" data-parent="#accordion">
                    <div class="panel-body px-0 px-md-3 py-4 border-top">
                        <p>${prediction.personalised_prediction}</p>
                    </div>
                </div>
            </div>`;
                }).join('');

                return `
        <div class="row">
            <div class="col-12">
                <h2 class="font-24 p-3">Predictions</h2>
            </div>
            <div class="col-12">
                <div class="panel-group my-1 p-3" id="accordion">${predictions}</div>
            </div>
        </div>`;
            }

            function generateShodashvargaHTML(response) {
                if (!response.charts) {
                    return `<p class="text-center">No Record Found</p>`;
                }

                var chartNames = {
                    'D1': 'Rasi',
                    'D2': 'Hora',
                    'D3': 'Drekkana',
                    'D4': 'Chaturthamsa',
                    'D5': 'Panchamamsa',
                    'D6': 'Shastamsa',
                    'D7': 'Saptamsa',
                    'D8': 'Astamsa',
                    'D9': 'Navamsa',
                    'D10': 'Dasamsa',
                    'D11': 'Rudramsa',
                    'D12': 'Dwadasamsa',
                    'D16': 'Shodasamsa',
                    'D20': 'Vimsamsa',
                    'D24': 'Siddhamsa',
                    'D27': 'Nakshatramsa',
                    'D30': 'Trimsamsa',
                    'D40': 'Khavedamsa',
                    'D45': 'Akshavedamsa',
                    'D60': 'Shastyamsa',
                    'chalit': 'Chalit',
                    'sun': 'Sun',
                    'moon': 'Moon',
                    'kp_chalit': 'Kp Chalit'
                };

                var charts = Object.keys(response.charts).map(key => `
        <div class="col-md-4 col-sm-6 col-12 mt-3">
            <p class="font-16 mb-1"><strong>${chartNames[key] || key}</strong></p>
            <div class="svg-container">${response.charts[key]}</div>
        </div>
    `).join('');

                return `
        <h2 class="p-3">Horoscope Chart</h2>
        <div class="row p-3">${charts}</div>`;
            }

            function generateAshtakvargaHTML(response) {
                if (response.ashtakvarga.status === 400) {
                    return `<p class="text-center">No Record Found</p>`;
                }

                var ashtakvargaRows = response.ashtakvarga.response.ashtakvarga_order
                    .filter(name => name !== 'Ascendant')
                    .map((name, index) => `
            <tr>
                <td>${name}</td>
                ${response.ashtakvarga.response.ashtakvarga_points[index].map(point => `<td>${point}</td>`).join('')}
            </tr>
        `).join('');

                var binnashtakvargaRows = Array.from({
                    length: 12
                }, (_, i) => `
        <tr>
            ${Object.values(response.binnashtakvarga.response).map(points => `<td>${points[i]}</td>`).join('')}
        </tr>
    `).join('');

                return `
        <div class="row">
            <div class="col-12">
                <h2 class="font-24 p-3">Ashtakvarga</h2>
            </div>
            <div class="col-12">
                <div class="table-responsive table-theme shadow-pink mb-3 p-3">
                    <table class="table table-bordered border-pink font-14 mb-0">
                        <thead class="font-13">
                            <tr class="bg-pink color-red font-weight-normal">
                                <th class="cellhead">&nbsp;</th>
                                <th>Ar</th><th>Ta</th><th>Ge</th><th>Ca</th><th>Le</th><th>Vi</th>
                                <th>Li</th><th>Sc</th><th>Sa</th><th>Ca</th><th>Aq</th><th>Pi</th>
                            </tr>
                        </thead>
                        <tbody>${ashtakvargaRows}</tbody>
                    </table>
                </div>
            </div>
            <div class="col-12">
                <h2 class="font-24 p-3">Binnashtakvarga</h2>
            </div>
            <div class="col-12">
                <div class="table-responsive table-theme shadow-pink mb-3 p-3">
                    <table class="table table-bordered border-pink font-14 mb-0">
                        <thead class="font-13">
                            <tr class="bg-pink color-red font-weight-normal">
                                ${Object.keys(response.binnashtakvarga.response).map(name => `<th>${name}</th>`).join('')}
                            </tr>
                        </thead>
                        <tbody>${binnashtakvargaRows}</tbody>
                    </table>
                </div>
            </div>
        </div>`;
            }

            function generateMahadashaHTML(response) {
                if (response.mahaDasha.status === 400) {
                    return `<p class="text-center">No Record Found</p>`;
                }

                var mahadashaRows = response.mahaDasha.response.mahadasha.map((dasha, index) => `
        <tr>
            <td>${dasha}</td>
            <td>${response.mahaDasha.response.mahadasha_order[index]}</td>
        </tr>
    `).join('');

                var predictions = response.mahaDashaPrediction.response.dashas.map(prediction => `
        <div class="prediction-block mb-4 p-3">
            <h4 class="font-18">${prediction.dasha} (${prediction.dasha_start_year} - ${prediction.dasha_end_year})</h4>
            <p class="font-14"><strong>Prediction:</strong> ${prediction.prediction}</p>
            <p class="font-14"><strong>Planet in Zodiac:</strong> ${prediction.planet_in_zodiac}</p>
        </div>
    `).join('');

                return `
        <div class="row">
            <div class="col-12">
                <h2 class="font-24 p-3">Mahadasha</h2>
            </div>
            <div class="col-12">
                <div class="table-responsive table-theme shadow-pink mb-3 p-3">
                    <table class="table table-bordered border-pink font-14 mb-0">
                        <thead class="font-13">
                            <tr class="bg-pink color-red font-weight-normal">
                                <th class="cellhead">MahaDasha</th>
                                <th class="cellhead">MahaDasha Order</th>
                            </tr>
                        </thead>
                        <tbody>${mahadashaRows}</tbody>
                    </table>
                </div>
            </div>
            <div class="col-12">
                <h3 class="font-20 mb-2 p-3">Mahadasha Predictions</h3>
            </div>
            <div class="col-12">${predictions}</div>
        </div>`;
            }

            function generateYoginiDashaHTML(response) {
                if (response.yoginiDashaMain.status === 400) {
                    return `<p class="text-center">No Record Found</p>`;
                }

                var rows = response.yoginiDashaMain.response.dasha_list.map((dasha, index) => `
        <tr>
            <td>${dasha}</td>
            <td>${response.yoginiDashaMain.response.dasha_lord_list[index]}</td>
            <td>${response.yoginiDashaMain.response.dasha_end_dates[index]}</td>
        </tr>
    `).join('');

                return `
        <div class="row">
            <div class="col-12">
                <div class="table-responsive table-theme shadow-pink mb-3 p-3">
                    <table class="table table-bordered border-pink font-14 mb-0">
                        <thead class="font-13">
                            <tr class="bg-pink color-red font-weight-normal">
                                <th class="cellhead">Dasha</th>
                                <th class="cellhead">Dasha Lord</th>
                                <th class="cellhead">End Date</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </div>
        </div>`;
            }

            function generateDoshaHTML(response) {
                var doshas = ['mangalDosh', 'kaalsarpDosh', 'manglikDosh', 'pitraDosh', 'papasamayaDosh'];
                var doshaHTML = doshas.map(dosha => {
                    if (response[dosha].status === 400) {
                        return `<p class="text-center">No Record Found for ${dosha}</p>`;
                    }

                    return `
            <div class="col-12 mb-3">
                <div class="table-responsive table-theme shadow-pink p-3">
                    <table class="table table-bordered border-pink font-14 mb-0">
                        <thead class="font-13">
                            <tr class="bg-pink color-red font-weight-normal">
                                <th class="cellhead" colspan="2">${dosha.replace(/([A-Z])/g, ' $1').trim()}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="2">
                                    <p>${response[dosha].response.bot_response}</p>
                                    ${response[dosha].response.remedies ? `
                                            <h5 class="font-16">Remedies</h5>
                                            <div class="dosha-remedies">
                                                ${response[dosha].response.remedies.map(remedy => `<p>${remedy}</p>`).join('')}
                                            </div>
                                        ` : ''}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>`;
                }).join('');

                return `
        <div class="row">
            <div class="col-12">
                <h2 class="font-24 p-3">Doshas</h2>
            </div>
            ${doshaHTML}
        </div>`;
            }

            function generateReportHTML(response) {
                var ascendantReport = response.ascendantReport.status === 200 ? `
        <div class="col-12">
            <h2 class="font-24 p-3">Ascendant Report</h2>
            <div class="table-responsive table-theme shadow-pink mb-3 p-3">
                <table class="table table-bordered border-pink font-14 mb-0">
                    <thead class="font-13">
                        <tr class="bg-pink color-red font-weight-normal">
                            <th class="cellhead">Aspect</th>
                            <th class="cellhead">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${response.ascendantReport.response.map(ascendant => `
                                <tr><td><strong>Ascendant</strong></td><td>${ascendant.ascendant}</td></tr>
                                <tr><td><strong>Ascendant Lord</strong></td><td>${ascendant.ascendant_lord}</td></tr>
                                <tr><td><strong>Ascendant Lord Location</strong></td><td>${ascendant.ascendant_lord_location} (${ascendant.ascendant_lord_house_location}th house)</td></tr>
                                <tr><td><strong>General Prediction</strong></td><td>${ascendant.general_prediction}</td></tr>
                                <tr><td><strong>Personalized Prediction</strong></td><td>${ascendant.personalised_prediction}</td></tr>
                                <tr><td><strong>Verbal Location</strong></td><td>${ascendant.verbal_location}</td></tr>
                                <tr><td><strong>Ascendant Lord Strength</strong></td><td>${ascendant.ascendant_lord_strength}</td></tr>
                                <tr><td><strong>Symbol</strong></td><td>${ascendant.symbol}</td></tr>
                                <tr><td><strong>Zodiac Characteristics</strong></td><td>${ascendant.zodiac_characteristics}</td></tr>
                                <tr><td><strong>Lucky Gem</strong></td><td>${ascendant.lucky_gem}</td></tr>
                                <tr><td><strong>Day for Fasting</strong></td><td>${ascendant.day_for_fasting}</td></tr>
                                <tr><td><strong>Gayatri Mantra</strong></td><td>${ascendant.gayatri_mantra}</td></tr>
                                <tr><td><strong>Flagship Qualities</strong></td><td>${ascendant.flagship_qualities}</td></tr>
                                <tr><td><strong>Spirituality Advice</strong></td><td>${ascendant.spirituality_advice}</td></tr>
                                <tr><td><strong>Good Qualities</strong></td><td>${ascendant.good_qualities}</td></tr>
                                <tr><td><strong>Bad Qualities</strong></td><td>${ascendant.bad_qualities}</td></tr>
                            `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    ` : `<p class="text-center">No Ascendant Record Found</p>`;

                var planetReport = Object.keys(response.planetReport).map(planet => {
                    if (response.planetReport[planet].status === 200) {
                        return response.planetReport[planet].response.map(planetDetails => `
                <div class="col-12">
                    <div class="table-responsive table-theme shadow-pink mb-3 p-3">
                        <table class="table table-bordered border-pink font-14 mb-0">
                            <thead class="font-13">
                                <tr class="bg-pink color-red font-weight-normal">
                                    <th class="cellhead">
                                                                        <th class="cellhead">${planet} Report</th>
                                    <th class="cellhead">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td><strong>Planet Location</strong></td><td>${planetDetails.planet_location} (${planetDetails.planet_native_location}th house)</td></tr>
                                <tr><td><strong>Planet Zodiac</strong></td><td>${planetDetails.planet_zodiac}</td></tr>
                                <tr><td><strong>Zodiac Lord</strong></td><td>${planetDetails.zodiac_lord}</td></tr>
                                <tr><td><strong>Zodiac Lord Location</strong></td><td>${planetDetails.zodiac_lord_location} (${planetDetails.zodiac_lord_house_location}th house)</td></tr>
                                <tr><td><strong>General Prediction</strong></td><td>${planetDetails.general_prediction}</td></tr>
                                <tr><td><strong>Planet Definitions</strong></td><td>${planetDetails.planet_definitions}</td></tr>
                                <tr><td><strong>Gayatri Mantra</strong></td><td>${planetDetails.gayatri_mantra}</td></tr>
                                <tr><td><strong>Qualities Long</strong></td><td>${planetDetails.qualities_long}</td></tr>
                                <tr><td><strong>Qualities Short</strong></td><td>${planetDetails.qualities_short}</td></tr>
                                <tr><td><strong>Affliction</strong></td><td>${planetDetails.affliction}</td></tr>
                                <tr><td><strong>Personalized Prediction</strong></td><td>${planetDetails.personalised_prediction || ''}</td></tr>
                                <tr><td><strong>Verbal Location</strong></td><td>${planetDetails.verbal_location}</td></tr>
                                <tr><td><strong>Planet Zodiac Prediction</strong></td><td>${planetDetails.planet_zodiac_prediction}</td></tr>
                                <tr><td><strong>Character Keywords Positive</strong></td><td>${planetDetails.character_keywords_positive.join(', ')}</td></tr>
                                <tr><td><strong>Character Keywords Negative</strong></td><td>${planetDetails.character_keywords_negative.join(', ')}</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            `).join('');
                    } else {
                        return `<p class="text-center">No ${planet} Record Found</p>`;
                    }
                }).join('');

                return `
        <div class="row">
            ${ascendantReport}
            <div class="col-12 mt-4">
                <h2 class="font-24 p-3">Planet Report</h2>
                ${planetReport}
            </div>
        </div>`;
            }

        });
    </script>
@endsection
