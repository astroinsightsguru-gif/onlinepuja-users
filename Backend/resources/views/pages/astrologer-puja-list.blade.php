@extends('../layout/' . $layout)

@section('subhead')
<title>Puja list</title>
@endsection

@section('subcontent')
@php
 $defaultImage = 'public/frontend/homeimage/360.png';
@endphp
<div class="loader"></div>
<h2 class="intro-y text-lg font-medium mt-10 d-inline mb-2">{{ucfirst($professionTitle)}} Puja list</h2>

 <!-- BEGIN: Data List -->
 <div class="w-full sm:w-auto mt-3 sm:mt-0 sm:ml-auto md:ml-0">
    <form action="{{ route('astrologer-puja-list') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="w-56 relative text-slate-500" style="display:inline-block">
            <input value="{{ $searchString }}" type="text" class="form-control w-56 box pr-10"
                placeholder="Search..." id="searchString" name="searchString">
            @if (!$searchString)
                <i class="w-4 h-4 absolute my-auto inset-y-0 mr-3 right-0" data-lucide="search"></i>
            @else
                <a href="{{ route('astrologer-puja-list') }}"><i class="w-4 h-4 absolute my-auto inset-y-0 mr-3 right-0"
                        data-lucide="x"></i></a>
            @endif
        </div>
        <button class="btn btn-primary shadow-md mr-2">Search</button>
    </form>
</div>

@if ($totalRecords > 0)
   
    <div class="intro-y col-span-12 overflow-auto lg:overflow-visible list-table">
        <table class="table table-report mt-2" aria-label="customer-list">
            <thead class="sticky-top">
                <tr>
                    <th class="whitespace-nowrap">#</th>
                    <th class="whitespace-nowrap">{{strtoupper($professionTitle)}}</th>
                    <th class="whitespace-nowrap text-center">PUJA TITLE</th>
                    <th class="whitespace-nowrap text-center">PUJA IMAGE</th>
                    <th class="text-center whitespace-nowrap">PUJA PRICE</th>
                    <th class="text-center whitespace-nowrap">PUJA PLACE</th>
                    <th class="text-center whitespace-nowrap">PUJA START</th>
                    <th class="text-center whitespace-nowrap">PUJA DURATION</th>
                    <th class="text-center whitespace-nowrap">ACTIONS</th>
                </tr>
            </thead>
            <tbody id="todo-list">
                @php
                    $no = 0;
                @endphp
                @foreach ($pujalist as $puja)
                    <tr class="intro-x">
                        <td>{{ ($page - 1) * 15 + ++$no }}</td>
                        <td>
                            <div class="font-medium whitespace-nowrap">{{ $puja->astrologer()->name ? $puja->astrologer()->name : '--' }}</div>
                        </td>
                        <td>
                            <div class="font-medium whitespace-nowrap text-center">{{ $puja->puja_title ? $puja->puja_title : '--' }}</div>
                        </td>
                        {{-- @php
                        $firstImage = $puja->puja_images[0] ?? 'public/frontend/homeimage/360.png';
                         @endphp --}}

                         @php
                           
                            $imagePaths = [];
                        
                            if (!empty($puja->puja_images)) {
                                // Decode if it's a JSON string
                                $mediaPath = is_array($puja->puja_images) ? $puja->puja_images : json_decode($puja->puja_images, true);
                                
                                if (is_array($mediaPath)) {
                                    foreach ($mediaPath as $path) {
                                        $imagePaths[] = $path; // Add each image path
                                    }
                                }
                            }
                        
                            if (empty($imagePaths)) {
                                $imagePaths[] = $defaultImage;
                            }
                        @endphp
                     
                        <td class="text-center">
                            <div class="flex " style="justify-content: center">
                                <div class="w-10 h-10 image-fit zoom-in">
                                    <img class="rounded-full" src="{{ asset($imagePaths[0]) }}"
                                        onerror="this.onerror=null;this.src='{{ asset($defaultImage) }}';"
                                        alt="{{ucfirst($professionTitle)}} image" onclick="showImageModal({{ json_encode(array_map('asset', $imagePaths)) }})"/>
                                </div>
                            </div>
                        </td>
                        <td class="text-center">₹{{ $puja->puja_price ? $puja->puja_price : '--' }}</td>
                        <td class="text-center">{{ $puja->puja_place ? $puja->puja_place : '--' }}</td>
                        <td class="text-center">{{ date('d-m-Y h:i a', strtotime($puja->puja_start_datetime ? $puja->puja_start_datetime : '--')) }}</td>
                        <td class="text-center">{{$puja->puja_duration}} mins</td>
                         <td class="table-report__action w-56">
                            <div class="flex justify-center items-center">
                                @if(\Carbon\Carbon::now()->lt(\Carbon\Carbon::parse($puja->puja_start_datetime)))
                                   
                                <a 
                                onclick="editbtn({{ $puja->id }}, '{{ $puja->isAdminApproved }}')" 
                                data-tw-toggle="modal" 
                                data-tw-target="#verifiedAstrologer" 
                                href="javascript:;" 
                                class="flex items-center mr-3 text-success"
                            >
                                <i 
                                    data-lucide="{{ $puja->isAdminApproved == 'Pending' ? 'lock' : 'unlock' }}" 
                                    class="w-4 h-4 mr-1" 
                                    style="{{ $puja->isAdminApproved == 'Pending' || $puja->isAdminApproved == 'Rejected' ? 'color:brown;' : '' }}"
                                ></i>
                                <span style="{{ $puja->isAdminApproved == 'Pending' || $puja->isAdminApproved == 'Rejected' ? 'color:brown;' : '' }}">
                                    {{ $puja->isAdminApproved }}
                                </span>
                            </a>
                            
                                @else
                                    <span class="flex items-center mr-3 text-gray-500">Date has been passed</span>
                                @endif
                              
                                {{-- <a type="button" href="javascript:;" class="flex items-center deletebtn text-danger"
                                    data-tw-toggle="modal" data-tw-target="#deleteModal" onclick="delbtn({{ $puja->id }})">
                                    <i data-lucide="trash-2" class="w-4 h-4 mr-1"></i>Delete
                                </a> --}}
                              
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <!-- END: Data List -->
    <!-- BEGIN: Pagination -->
    @if ($totalRecords > 0)
        <div class="d-inline text-slate-500 pagecount">Showing {{ $start }} to {{ $end }} of
            {{ $totalRecords }} entries
        </div>
    @endif
    <div class="d-inline addbtn intro-y col-span-12">
        <nav class="w-full sm:w-auto sm:mr-auto">
            <ul class="pagination">
                <li class="page-item {{ $page == 1 ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ route('astrologer-puja-list', ['page' => $page - 1]) }}">
                        <i class="w-4 h-4" data-lucide="chevron-left"></i>
                    </a>
                </li>
                @for ($i = 0; $i < $totalPages; $i++)
                    <li class="page-item {{ $page == $i + 1 ? 'active' : '' }} ">
                        <a class="page-link" href="{{ route('astrologer-puja-list', ['page' => $i + 1]) }}">{{ $i + 1 }}</a>
                    </li>
                @endfor
                <li class="page-item {{ $page == $totalPages ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ route('astrologer-puja-list', ['page' => $page + 1]) }}">
                        <i class="w-4 h-4" data-lucide="chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
@else
    <div class="intro-y mt-5" style="height:100%">
        <div style="display:flex;align-items:center;height:100%;">
            <div style="margin:auto">
                <img src="/build/assets/images/nodata.png" style="height:290px" alt="noData">
                <h3 class="text-center">No Data Available</h3>
            </div>
        </div>
    </div>
@endif
<!-- END: Pagination -->

<div id="deleteModal" class="modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-5 text-center">
                    <i data-lucide="x-circle" class="w-16 h-16 text-danger mx-auto mt-3"></i>
                    <div class="text-3xl mt-5">Are you sure?</div>
                    <div class="text-slate-500 mt-2">Do you really want to delete these records? <br>This process
                        cannot be undone.</div>
                </div>
                <form action="{{ route('deletePuja') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" id="del_id" name="del_id">
                    <div class="px-5 pb-8 text-center">
                        <button type="button" data-tw-dismiss="modal"
                            class="btn btn-outline-secondary w-24 mr-1">Cancel</button>
                        <button class="btn btn-danger w-24">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

 <!-- BEGIN: Modal Content -->
 <div id="verifiedAstrologer" class="modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body p-0">
                <div class="p-5 text-center">
                    <div class="text-3xl mt-5">Are You Sure?</div>
                    <div class="text-slate-500 mt-2" id="verified">You want Verified!</div>
                </div>
                <form action="{{ route('adminPujaApproveStatus') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="filed_id" name="filed_id">
                    <input type="hidden" id="status_value" name="isAdminApproved">
                    <div class="px-5 pb-8 text-center"><button class="btn btn-primary mr-3" id="btnVerified">Yes,
                            Verified it!
                        </button><a type="button" data-tw-dismiss="modal" class="btn btn-secondary w-24"> Cancel</a>
                    </div>
            </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="imageModal" style="display: none; position: fixed; inset: 0; background: rgba(0, 0, 0, 0.8); justify-content: center; align-items: center; z-index: 1000;">
    <div style="position: relative; max-width: 90%; max-height: 90%;">
        <!-- Full Image -->
        <img id="modalImage" src="" alt="Full View" style="width: 600px; height: 500px; border-radius: 8px;" />
        <!-- Close Button -->
        <button onclick="closeImageModal()" style="position: absolute; top: 10px; right: 10px; background: red; color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 16px;">×</button>
        <!-- Navigation Buttons -->
        <button id="prevButton" onclick="navigateSlide(-1)" style="position: absolute; top: 50%; left: 10px; transform: translateY(-50%); background: rgba(0, 0, 0, 0.6); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 20px;">‹</button>
        <button id="nextButton" onclick="navigateSlide(1)" style="position: absolute; top: 50%; right: 10px; transform: translateY(-50%); background: rgba(0, 0, 0, 0.6); color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 20px;">›</button>
    </div>
</div>

<!-- END: Delete Confirmation Modal -->
@endsection

@section('script')

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(document).ready(function () {
        jQuery('.select2').select2();
    });
</script>
<script>
    jQuery.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    }) 
</script>

<script type="text/javascript">
    @if (Session::has('error'))
        toastr.options = {
            "closeButton": true,
            "progressBar": true
        }
        toastr.warning("{{ session('error') }}");
    @endif
    function delbtn($id) {
        var id = $id;
        $did = id;

        $('#del_id').val($did);
        $('#id').val($id);
    }

    
    function editbtn(id, isAdminApproved) {
        $('#filed_id').val(id);

        let newStatus = '';
        let buttonText = '';

        if (isAdminApproved === 'Pending' || isAdminApproved === 'Rejected') {
            newStatus = 'Approved';
            buttonText = 'Yes, Approve it!';
        } else if (isAdminApproved === 'Approved') {
            newStatus = 'Rejected';
            buttonText = 'Yes, Reject it!';
        }

        // Set modal text
        document.getElementById('verified').innerHTML = "You want to " + newStatus + "?";
        document.getElementById('btnVerified').innerHTML = buttonText;

        // Set status for backend
        $('#status_value').val(newStatus);
    }


</script>
<script type="text/javascript">
    var spinner = $('.loader');
</script>
<script>
    $(window).on('load', function () {
        $('.loader').hide();
    })
</script>

 
     <script>
        $(window).on('load', function() {
            $('.loader').hide();
        })

        let currentSlideIndex = 0;
        let slideImages = [];

        function showImageModal(images) {
            slideImages = images;
            currentSlideIndex = 0;
            updateModalImage();
            document.getElementById('imageModal').style.display = 'flex';
        }

        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        function navigateSlide(direction) {
            if (slideImages.length > 0) {
                currentSlideIndex = (currentSlideIndex + direction + slideImages.length) % slideImages.length;
                updateModalImage();
            }
        }
        
        

        function updateModalImage() {
            const modalImage = document.getElementById('modalImage');
            if (slideImages[currentSlideIndex]) {
                modalImage.src = slideImages[currentSlideIndex];
            } else {
                modalImage.src = '{{ asset($defaultImage) }}';
            }
        }

    </script>
@endsection