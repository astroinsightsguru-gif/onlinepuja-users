@extends('frontend.layout.master')
@section('content')
<style>
    .psychic-card .btn {
    min-width: 130px;
    height: 35px;
    font-size: 20px;
}
</style>
    <div class="ds-head-body">

        <div class="container">
            <div class="row">
                <div class="col-sm-12 expert-search-section-height-favourites">
                    <h1 class="h2 font-weight-bold colorblack mb-1 mb-md-4 mt-sm-3">
                        Blocked {{$professionTitle}}
                    </h1>
                    @if ($getblockastro['recordList']==null)

                        <div class="text-center mt-5 text-bold" colspan="6">
                            <h3>No Blocked {{$professionTitle}}  Found !</h3>
                        </div>

                    @else
                    <div class="list py-4 " id="expert-list">
                        @foreach ($getblockastro['recordList'] as $getblockastro)
                            <div id="psychic-card-618160" class="psychic-card overflow-hidden  expertOnline  "
                                data-psychic-id="618160">
                                <ul class="list-unstyled d-flex mb-0">
                                    <li class="mr-3 position-relative psychic-presence status-618160"
                                        data-status="Available" data-chata="₹0" data-calla="₹ 0"><a
                                            href="{{ route('front.astrologerDetails', ['slug' => $getblockastro['slug'] ?? ($getblockastro['astrologerId'] ?? 1)]) }}">
                                            <div class="psyich-img position-relative">
                                                <img class="rounded-full cursor-pointer" src="{{ Str::startsWith($getblockastro['profile'], ['http://','https://']) ? $getblockastro['profile'] : '/' . $getblockastro['profile'] }}" onerror="this.onerror=null;this.src='/build/assets/images/person.png';" alt="Customer image" onclick="openImage('{{ $getblockastro['profile'] }}')" />
                                            </div>
                                        </a>
                                        <div id="psychic-618160-badge" class="status-badge specific-Clr-Online"
                                            title="Online">
                                        </div>
                                        <div class="status-badge-txt text-center specific-Clr-Online"><span
                                                id="psychic-618160-badge-txt"></span></div>
                                    </li>
                                    <li class="w-100 overflow-hidden"><a
                                            href="{{ route('front.astrologerDetails', ['slug' => $getblockastro['slug'] ?? ($getblockastro['id'] ?? 1)]) }}"
                                            class="colorblack font-weight-semi font16 mt-0 ml-0 mr-0 mb-0 p-0 text-capitalize d-block"
                                            data-toggle="tooltip">{{ $getblockastro['astrologerName'] }}</a><span
                                            class="font-12 d-block color-red">{{ $getblockastro['allSkill'] }}</span><span
                                            class="font-12 d-block exp-language">{{ $getblockastro['languageKnown'] }}</span><span
                                            class="font-12 d-block"> Exp :{{ $getblockastro['experienceInYears'] }}</span>
                                    </li>
                                </ul>
                                <div class="">
                                    <div class="d-block">
                                        <div class="d-flex justify-content-end">
                                            <form class="unblockastroform">
                                                <input type="hidden" name="astrologerId" value="{{$getblockastro['astrologerId']}}">
                                            <a class="btn-block btn btn-report unblockastro" role="button"
                                               >Unblock</a></div>
                                            </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>


@endsection

@section('scripts')

<script>
$(document).ready(function() {
    $('.unblockastro').click(function(e) {
        e.preventDefault();

        @php
            use Symfony\Component\HttpFoundation\Session\Session;
            $session = new Session();
            $token = $session->get('token');
        @endphp

        Swal.fire({
            title: 'Are you sure you want to unblock?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, unblock it!'
        }).then((result) => {
            if (result.isConfirmed) {
                var formData = $(this).closest('.unblockastroform').serialize();
                // console.log(formData);

                $.ajax({
                    url: '{{ route("api.unblockAstrologer",['token' => $token]) }}',
                    type: 'POST',
                    data: formData,

                    success: function(response) {
                        toastr.success('Unblocked Successfully');
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    },
                    error: function(xhr, status, error) {
                        var errorMessage = JSON.parse(xhr.responseText).error.paymentMethod[0];
                        toastr.error(errorMessage);
                    }
                });
            }
        });
    });
});

</script>


@endsection
