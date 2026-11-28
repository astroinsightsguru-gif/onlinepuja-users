@extends('../layout/' . $layout)

@section('subhead')
<title>Puja list</title>
@endsection

@section('subcontent')
<div class="loader"></div>
<h2 class="intro-y text-lg font-medium mt-10 d-inline">Puja list</h2>

<a class="btn btn-primary shadow-md mr-2 mt-10 d-inline addbtn" href="puja/add">Add Puja</a>
@if ($totalRecords > 0)
    <!-- BEGIN: Data List -->
    <div class="intro-y col-span-12 overflow-auto lg:overflow-visible list-table">
        <table class="table table-report mt-2" aria-label="customer-list">
            <thead class="sticky-top">
                <tr>
                    <th class="whitespace-nowrap">#</th>
                    <th>Image</th>
                    <th class="whitespace-nowrap">TITLE</th>
                    <th class="whitespace-nowrap text-center">SUB TITLE</th>
                    <th class="text-center whitespace-nowrap">PUJA PLACE</th>
                    <th class="text-center whitespace-nowrap">Status</th>
                    <th class="text-center whitespace-nowrap">ACTIONS</th>
                    <th class="text-center whitespace-nowrap">Message</th>
                </tr>
            </thead>
            <tbody id="todo-list">
                @php
                    $no = 0;
                @endphp
                @foreach ($pujalist as $puja)
    <tr class="intro-x">
        <td>{{ ($page - 1) * 15 + ++$no }}</td>
        <td class="text-center">
            <div class="flex items-center">
                <div class="image-fit zoom-in" style="height:2.3rem;width:2.3rem;">
                    @php
                        $firstImage = !empty($puja->puja_images) ? $puja->puja_images[0] : null;
                    @endphp
        
                    @if($firstImage)
                        <img class="rounded-full cursor-pointer"
                             src="{{ Str::startsWith( $firstImage, ['http://','https://']) ? $firstImage : asset($firstImage) }}"
                             onerror="this.onerror=null;this.src='/public/frontend/homeimage/360.png';"
                             alt="Customer image"
                             onclick="openImage('{{ Str::startsWith( $firstImage, ['http://','https://']) ? $firstImage : asset($firstImage) }}')" />
                    @else
                        <img class="rounded-full"
                             src="/public/frontend/homeimage/360.png"
                             onerror="this.onerror=null;this.src='/public/frontend/homeimage/360.png';"
                             alt="image" />
                    @endif
                </div>
            </div>
        </td>

        <td>
            <div class="font-medium whitespace-nowrap">{{ $puja->puja_title ? $puja->puja_title : '--' }}</div>
        </td>
        <td class="text-center">{{ $puja->puja_subtitle ? $puja->puja_subtitle : '--' }}</td>
        <td class="text-center">{{ $puja->puja_place ? $puja->puja_place : '--' }}</td>
        <td class="text-center">
            <div class="flex justify-center items-center">
                <div class="form-check form-switch justify-center w-full sm:w-auto sm:ml-auto mt-3 sm:mt-0 mr-3">
                    <input class="toggle-class show-code form-check-input mr-3 ml-3" type="checkbox"
                        data-tw-toggle="modal" data-onstyle="success" data-offstyle="danger" data-toggle="toggle"
                        data-on="Active" data-off="InActive"
                        {{ $puja->puja_status ? 'checked' : '' }}
                        onclick="editPuja({{ $puja->id }},{{ $puja->puja_status }})"
                        data-tw-target="#verified">
                </div>
            </div>
        </td>
        <td class="table-report__action w-56">
            <div class="flex justify-center items-center">
                <a class="flex items-center mr-3" href="{{ route('edit-puja', $puja->id) }}">
                    <i data-lucide="check-square" class="w-4 h-4 mr-1"></i>Edit
                </a>
                <a type="button" href="javascript:;" class="flex items-center deletebtn text-danger"
                    data-tw-toggle="modal" data-tw-target="#deleteModal" onclick="delbtn({{ $puja->id }})">
                    <i data-lucide="trash-2" class="w-4 h-4 mr-1"></i>Delete
                </a>
                <a class="flex items-center ml-3" href="{{ route('view-puja', $puja->id) }}">
                    <i data-lucide="eye" class="w-4 h-4 mr-1"></i>View
                </a>
            </div>
        </td>
        <td class="text-center">
            @if($puja->puja_start_datetime && \Carbon\Carbon::now()->gt(\Carbon\Carbon::parse($puja->puja_start_datetime)))
                    <span class="flex items-center ml-3 text-gray-500">(Date has been passed)</span>
                @endif
        </td>
    </tr>
@endforeach

            </tbody>
        </table>
    </div>
    <!-- Fullscreen -->
<div class="image-overlay" id="imageOverlay">
                <img src="your-image.jpg" id="popupImage" alt="Full Screen Image">
                <span class="closebtn" id="closeBtn">&times;</span>
            </div>
            <script>
            const overlay = document.getElementById('imageOverlay');
            const closeBtn = document.getElementById('closeBtn');
            function openImage(src) {
                document.getElementById('popupImage').src = src;
                overlay.classList.add('active');
            }
            closeBtn.addEventListener('click', () => {
                overlay.classList.remove('active');
            });
            overlay.addEventListener('click', (e) => {
                if(e.target === overlay) {
                    overlay.classList.remove('active');
                }
            });
            </script>
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
                    <a class="page-link" href="{{ route('puja-list', ['page' => $page - 1]) }}">
                        <i class="w-4 h-4" data-lucide="chevron-left"></i>
                    </a>
                </li>
                @for ($i = 0; $i < $totalPages; $i++)
                    <li class="page-item {{ $page == $i + 1 ? 'active' : '' }} ">
                        <a class="page-link" href="{{ route('puja-list', ['page' => $i + 1]) }}">{{ $i + 1 }}</a>
                    </li>
                @endfor
                <li class="page-item {{ $page == $totalPages ? 'disabled' : '' }}">
                    <a class="page-link" href="{{ route('puja-list', ['page' => $page + 1]) }}">
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
                <img src="build/assets/images/nodata.png" style="height:290px" alt="noData">
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

<div id="verified" class="modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body p-0">
                    <div class="p-5 text-center">
                        <div class="text-3xl mt-5">Are You Sure?</div>
                        <div class="text-slate-500 mt-2" id="active">You want Active!</div>
                    </div>
                    <form action="{{ route('PujaStatus') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" id="status_id" name="status_id">
                        <div class="px-5 pb-8 text-center"><button class="btn btn-primary mr-3" id="btnActive">Yes,
                                Active it!
                            </button><a type="button" data-tw-dismiss="modal" class="btn btn-secondary btn-submit w-24"
                                onclick="location.reload();">Cancel</a>
                        </div>
                    </form>
                </div>
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


    function editPuja($id, $isActive) {
                var id = $id;
                $fid = id;
                var active = $isActive ? 'Inactive' : 'Active';
                document.getElementById('active').innerHTML = "You want to " + active;
                document.getElementById('btnActive').innerHTML = "Yes, " +
                    active + " it";

                $('#status_id').val($fid);
                $('#editName').val($name);
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
@endsection
