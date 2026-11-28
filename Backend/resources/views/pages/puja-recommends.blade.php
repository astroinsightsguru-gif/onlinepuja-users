@extends('../layout/' . $layout)

@section('subhead')
    <title>Puja Recommend List</title>
@endsection

@section('subcontent')
    <div class="loader"></div>
    <h2 class="intro-y text-lg font-medium mt-10 d-inline">Puja Recommends</h2>
    <div class="grid grid-cols-12 gap-6 mt-5">
        <div class="intro-y col-span-12 flex flex-wrap sm:flex-nowrap items-center mt-2">
            <div class="w-full sm:w-auto mt-3 sm:mt-0 sm:ml-auto md:ml-0">
            </div>
        </div>
    </div>
    @if ($totalRecords > 0)
        <!-- BEGIN: Data List -->
        <div class="intro-y col-span-12 overflow-auto lg:overflow-visible list-table">
            <table class="table table-report mt-2" aria-label="customer-list">
                <thead class="sticky-top">
                    <tr>
                        <th class="whitespace-nowrap">#</th>
                        <th class="whitespace-nowrap text-center">Puja</th>
                        <th class="whitespace-nowrap text-center">User</th>
                        <th class="whitespace-nowrap" text-center>{{ucfirst($professionTitle)}}</th>
                        <th class="whitespace-nowrap text-center">Purchased By User</th>
                        <th class="text-center whitespace-nowrap">Recommend Date</th>

                    </tr>
                </thead>
                <tbody id="todo-list">
                    @php
                        $no = 0;
                    @endphp
                    @foreach ($pujarecommend as $clist)
                    <?php 
                            $images = json_decode($clist->puja_images);
                            $firstImage = !empty($images) ? $images[0] : 'build/assets/images/person.png';
                            // dd($images);
                        ?>
                        <tr class="intro-x">
                            <td>{{ ($page - 1) * 15 + ++$no }}</td>
                            <td>
                        <div class="flex">
                            <div class="w-10 h-10 image-fit zoom-in">
                                <img class="rounded-full cursor-pointer"
                                                src="{{ Str::startsWith( $firstImage, ['http://','https://']) ? $firstImage : '/' . $firstImage }}"
                                                onerror="this.onerror=null;this.src='/build/assets/images/person.png';"
                                                alt="Customer image"
                                                onclick="openImage('{{ Str::startsWith( $firstImage, ['http://','https://']) ? $firstImage : '/' . $firstImage }}')" />
                            </div>
                        </div>
                    </td>
                            
                            <td>
                                <div class="font-medium whitespace-nowrap text-center">
                                    {{ $clist->userName ? $clist->userName : '-----' }}</div>
                            </td>
                            <td>
                                <div class="font-medium whitespace-nowrap text-center">
                                    {{ $clist->astrologerName ? $clist->astrologerName : '--' }}</div>
                            </td>
                            <td>
                                <div class="font-medium whitespace-nowrap text-center">
                                    {{ $clist->isPurchased ? 'Purchased' : 'Not Purchased' }}</div>
                            </td>
                            <td class="text-center">
                                {{ date('d-m-Y h:i a', strtotime($clist->recommDateTime)) ? date('d-m-Y h:i a', strtotime($clist->recommDateTime)) : '--' }}
                            </td>
                            {{-- <td>
                
                            <span data-bs-toggle="tooltip" data-bs-placement="top" title="{{ucwords($clist->contact_message)}}"
                            style="cursor: pointer;">{{(!empty($clist->contact_message) ? (strlen($clist->contact_message) > 50 ? ucwords(substr($clist->contact_message, 0, 50)) . ' ...' : ucwords($clist->contact_message)) : '- -')}}</span>
                            </td> --}}
                           
                           
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <!-- Fullscreen Image Viewer -->
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
                {{ $totalRecords }} entries</div>
        @endif
        <div class="d-inline intro-y col-span-12 addbtn ">
            <nav class="w-full sm:w-auto sm:mr-auto">
                <ul class="pagination" id="pagination">
                    <li class="page-item {{ $page == 1 ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ route('pujaRecommend', ['page' => $page - 1]) }}">
                            <i class="w-4 h-4" data-lucide="chevron-left"></i>
                        </a>
                    </li>
                    @for ($i = 0; $i < $totalPages; $i++)
                        <li class="page-item {{ $page == $i + 1 ? 'active' : '' }} ">
                            <a class="page-link"
                                href="{{ route('pujaRecommend', ['page' => $i + 1]) }}">{{ $i + 1 }}</a>
                        </li>
                    @endfor
                    <li class="page-item {{ $page == $totalPages ? 'disabled' : '' }}">
                        <a class="page-link" href="{{ route('pujaRecommend', ['page' => $page + 1]) }}">
                            <i class="w-4 h-4" data-lucide="chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    @else
        <div class="intro-y" style="height:100%">
            <div style="display:flex;align-items:center;height:100%;">
                <div style="margin:auto">
                    <img src="/build/assets/images/nodata.png" style="height:290px" alt="noData">
                    <h3 class="text-center">No Data Available</h3>
                </div>
            </div>
        </div>
    @endif
@endsection
@section('script')
    <script>
        $(window).on('load', function() {
            $('.loader').hide();
        })
    </script>
@endsection
