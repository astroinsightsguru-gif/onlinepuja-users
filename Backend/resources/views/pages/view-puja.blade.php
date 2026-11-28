@extends('../layout/' . $layout)

@section('subhead')
<title>View Puja</title>
@endsection

@section('subcontent')
<style>
    .upload__inputfile {
        width: 0.1px;
        height: 0.1px;
        opacity: 0;
        overflow: hidden;
        position: absolute;
        z-index: -1;
    }

    .upload__btn {
        display: inline-block;
        font-weight: 600;
        color: #fff;
        text-align: center;
        min-width: 116px;
        padding: 5px;
        transition: all 0.3s ease;
        cursor: pointer;
        border: 2px solid;
        background-color: #4045ba;
        border-color: #4045ba;
        border-radius: 10px;
        line-height: 26px;
        font-size: 14px;
    }

    .upload__btn:hover {
        background-color: unset;
        color: #4045ba;
        transition: all 0.3s ease;
    }

    .upload__btn-box {
        margin-bottom: 10px;
    }

    .upload__img-wrap {
        display: flex;
        flex-wrap: wrap;
        margin: 0 -10px;
    }

    .upload__img-box {
        width: 200px;
        padding: 0 10px;
        margin-bottom: 12px;
    }

    .upload__img-close {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background-color: rgba(0, 0, 0, 0.5);
        position: absolute;
        top: 10px;
        right: 10px;
        text-align: center;
        line-height: 24px;
        z-index: 1;
        cursor: pointer;
    }

    .upload__img-close:after {
        content: "✖";
        font-size: 14px;
        color: white;
    }

    .img-bg {
        background-repeat: no-repeat;
        background-position: center;
        background-size: cover;
        position: relative;
        padding-bottom: 100%;
    }
</style>

<div class="grid grid-cols-12 gap-6 mt-5">
    <div class="intro-y col-span-12 mt-2">
        <div class="intro-y box">
            <div
                class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60 dark:border-darkmode-400">
                <h2 class="font-medium text-base mr-auto">View Puja</h2>
            </div>
            <div class="p-5">
                <form action="" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <!-- Title and Subtitle (Col-6 Col-6) -->
                    <div class="grid grid-cols-12 gap-6">
                        <div class="col-span-12 sm:col-span-6">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" name="title" id="title" class="form-control w-full"
                                value="{{$puja->puja_title ?? ''}}" placeholder="Enter title" readonly>
                        </div>
                        <div class="col-span-12 sm:col-span-6">
                            <label for="subtitle" class="form-label">Subtitle</label>
                            <input type="text" name="subtitle" id="subtitle" class="form-control w-full"
                                value="{{$puja->puja_subtitle ?? ''}}" placeholder="Enter subtitle" readonly>
                        </div>
                    </div>
                    <div class="grid grid-cols-12 gap-6 mt-5">
                        <div class="intro-y col-span-6 md:col-span-6">
                            <label id="input-group" class="form-label">Start Date Time</label>
                            <input type="datetime-local" class="form-control" placeholder="FromTime"
                                name="puja_start_datetime" id="puja_start_datetime" aria-describedby="input-group-4"
                                value="{{ isset($puja) ? date('Y-m-d\TH:i', strtotime($puja->puja_start_datetime)) : '' }}" readonly>
                        </div>
                        <div class="intro-y col-span-6 md:col-span-6">
                            <label id="input-group" class="form-label">Puja Duration</label>
                            <input type="number" class="form-control" placeholder="Duration"
                                name="puja_duration" id="puja_duration" aria-describedby="input-group-4"
                                value="{{ isset($puja) ? $puja->puja_duration : '' }}" readonly>
                        </div>
                    </div>

                    <!-- Category and Place (Col-6 Col-6) -->
                    <div class="grid grid-cols-12 gap-6 mt-5">
                        <div class="col-span-12 sm:col-span-4">
                            <label for="category_id" class="form-label">Category</label>
                            <select name="category_id" id="category_id" class="form-select w-full" readonly>
                                <option value="">Select Category</option>
                                @foreach ($pujaCategory as $pujacat)
                                    <option value="{{ $pujacat->id }}" {{ (isset($puja) && $puja->category_id == $pujacat->id) ? 'selected' : '' }}>
                                        {{ $pujacat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-12 sm:col-span-4">
                            <label for="sub_category_id" class="form-label">Subcategory</label>
                            <select name="sub_category_id" id="sub_category_id" class="form-select w-full" readonly>
                                <option value="">Select Subcategory</option>
                                @if(isset($puja) && $puja->sub_category_id)
                                    @foreach ($pujaSubCategory as $pujasubcat)
                                        @if($pujasubcat->category_id == $puja->category_id)
                                            <option value="{{ $pujasubcat->id }}" {{ $puja->sub_category_id == $pujasubcat->id ? 'selected' : '' }}>
                                                {{ $pujasubcat->name }}
                                            </option>
                                        @endif
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-span-12 sm:col-span-4">
                            <label for="place" class="form-label">Place</label>
                            <input type="text" name="place" id="place" value="{{$puja->puja_place ?? ''}}"
                                class="form-control w-full" placeholder="Enter place" readonly>
                        </div>
                        <div class="col-span-12 sm:col-span-4">
                            <label for="package_id" class="form-label">Package</label>
                            <select name="package_id[]" id="package_id" class="form-control select2" multiple disabled>
                                <option value="">Select Package</option>
                                @foreach ($packages as $package)
                                    <option value="{{$package->id}}" @selected(isset($puja) && in_array($package->id, $puja->package_id))>
                                        {{$package->title}} - {{$package->package_price}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <!-- Description (Full Width) -->
                    <div class="mt-5">
                        <label for="description" class="form-label">About Puja</label>
                        <textarea name="description" readonly id="description" class="form-control w-full"
                            placeholder="Enter description">{{$puja->long_description ?? ''}}</textarea>
                    </div>
                    <!-- Puja Benefits Section -->
                    <div class="border border-gray-300 p-4 rounded mt-5">
                        <h3 class="text-lg font-medium">Puja Benefits</h3>

                        <div id="puja-benefits" class="grid grid-cols-12 gap-6 mt-3">

                            @if(isset($puja) && !empty($puja->puja_benefits) && is_array($puja->puja_benefits))
                                @foreach ($puja->puja_benefits as $benkey => $ben)

                                    <div class="col-span-12 sm:col-span-6 relative border border-gray-300 p-4 rounded mt-3">
                                        <h3 class="font-bold mb-2"> Benefit </h3>

                                        <input type="text" readonly name="benefit_title[]"
                                            class="form-control w-full mb-2 border border-gray-300 p-2 rounded"
                                            value="{{ $ben['title'] ?? '' }}" placeholder="Enter benefit title">

                                        <textarea readonly name="benefit_description[]"
                                            class="form-control w-full border border-gray-300 p-2 rounded"
                                            placeholder="Enter benefit description">{{ $ben['description'] ?? '' }}</textarea>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    <!-- end sections -->
                    <div class="upload__box mt-5">
                        <div class="upload__img-wrap">
                            @if(isset($puja) && !empty($puja->puja_images) && is_array($puja->puja_images))
                                @foreach ($puja->puja_images as $imgkey => $img)
                                    <div class="upload__img-box">
                                        <div style="background-image: url('{{ Str::startsWith($img, ['http://','https://']) ? $img : asset($img) }}');" class="img-bg"
                                            data-file="{{ $img }}">
                                            <input type="file" name="old_images[]" multiple style="display:none;">
                                            <input type="hidden" name="existing_images[]" value="{{ $img }}">
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>

    $(document).ready(function () {
        jQuery('.select2').select2({
            allowClear: true,
            tokenSeparators: [',', ' ']
        });
    });

    jQuery(document).ready(function () {
        ImgUpload();
    });

    function ImgUpload() {
        var imgWrap = "";
        var imgArray = [];

        $('.upload__inputfile').each(function () {
            $(this).on('change', function (e) {
                imgWrap = $(this).closest('.upload__box').find('.upload__img-wrap');
                var maxLength = $(this).attr('data-max_length');

                var files = e.target.files;
                var filesArr = Array.prototype.slice.call(files);
                var iterator = 0;
                filesArr.forEach(function (f) {

                    if (!f.type.match('image.*')) {
                        return;
                    }

                    if (imgArray.length >= maxLength) {
                        return false;
                    } else {
                        imgArray.push(f);

                        var reader = new FileReader();
                        reader.onload = function (e) {
                            var html = "<div class='upload__img-box'><div style='background-image: url(" + e.target.result + ")' data-file='" + f.name + "' class='img-bg'><div class='upload__img-close'></div></div></div>";
                            imgWrap.append(html);
                            iterator++;
                        }
                        reader.readAsDataURL(f);
                    }
                });
            });
        });

        $('body').on('click', ".upload__img-close", function () {
            var file = $(this).parent().data("file");
            imgArray = imgArray.filter(f => f.name !== file);
            $(this).closest('.upload__img-box').remove();
        });

        // Optionally reset imgArray on form submit
        $('form').on('submit', function () {
            imgArray = [];
        });
    }



    document.addEventListener('DOMContentLoaded', function () {
        let benefitCount = 0; // Initialize counter

        document.getElementById('add-benefit').addEventListener('click', function () {
            const benefitContainer = document.getElementById('puja-benefits');

            // Increment the counter
            benefitCount++;

            // Create benefit section
            const newBenefit = document.createElement('div');
            newBenefit.classList.add('col-span-12', 'sm:col-span-6', 'relative', 'border', 'border-gray-300', 'p-4', 'rounded', 'mt-3');

            // Create heading for benefit
            const heading = document.createElement('h3');
            heading.textContent = ` Benefit `;
            heading.classList.add('font-bold', 'mb-2');

            // Create input for benefit title
            const titleInput = document.createElement('input');
            titleInput.type = 'text';
            titleInput.name = 'benefit_title[]';
            titleInput.classList.add('form-control', 'w-full', 'mb-2', 'border', 'border-gray-300', 'p-2', 'rounded');
            titleInput.placeholder = 'Enter benefit title';

            // Create textarea for benefit description
            const descriptionTextarea = document.createElement('textarea');
            descriptionTextarea.name = 'benefit_description[]';
            descriptionTextarea.classList.add('form-control', 'w-full', 'border', 'border-gray-300', 'p-2', 'rounded');
            descriptionTextarea.placeholder = 'Enter benefit description';

            // Create remove button (badge style)
            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.innerHTML = '&times;';
            removeButton.classList.add('absolute', 'top-0', 'right-0', 'bg-red-500', 'text-danger', 'border', 'border-gray-800', 'rounded-full', 'w-5', 'h-5', 'flex', 'items-center', 'justify-center', 'cursor-pointer', 'text-sm', 'badge-button', 'shadow-md');

            // Remove benefit section on button click
            removeButton.addEventListener('click', function () {
                benefitContainer.removeChild(newBenefit);
            });

            // Append heading, title input, description textarea, and remove button to the new benefit
            newBenefit.appendChild(heading);
            newBenefit.appendChild(titleInput);
            newBenefit.appendChild(descriptionTextarea);
            newBenefit.appendChild(removeButton);

            // Append the new benefit to the benefit container
            benefitContainer.appendChild(newBenefit);
        });

        // Function to get ordinal number
        function ordinalNumber(num) {
            const suffix = ['th', 'st', 'nd', 'rd'];
            const value = num % 100;
            return num + (suffix[(value - 20) % 10] || suffix[value] || suffix[0]);
        }
    });

</script>
<script>
    @if ($errors->any())
        @foreach ($errors->all() as $error)
            toastr.error("{{ $error }}");
        @endforeach
    @endif
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('category_id');
        const subCategorySelect = document.getElementById('sub_category_id');

        // Function to load subcategories
        const loadSubcategories = (categoryId, selectedSubId = null) => {
            subCategorySelect.innerHTML = '<option value="">Select Subcategory</option>';

            if (categoryId) {
                // Prepare form data
                const formData = new FormData();
                formData.append('category_id', categoryId);

                // Add CSRF token if needed
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

                fetch('/api/getPujaSubCategory', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        ...(csrfToken ? {'X-CSRF-TOKEN': csrfToken} : {})
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.recordList && data.recordList.length > 0) {
                        data.recordList.forEach(subcategory => {
                            const option = new Option(subcategory.name, subcategory.id);
                            if (selectedSubId && subcategory.id == selectedSubId) {
                                option.selected = true;
                            }
                            subCategorySelect.add(option);
                        });
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        };

        // Handle category change
        categorySelect.addEventListener('change', function() {
            loadSubcategories(this.value);
        });

        // On page load, if editing with preselected category
        if (categorySelect.value) {
            const selectedSubId = {{ isset($puja) && $puja->sub_category_id ? $puja->sub_category_id : 'null' }};
            loadSubcategories(categorySelect.value, selectedSubId);
        }
    });
    </script>


@endsection
