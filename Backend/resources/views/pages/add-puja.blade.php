@extends('../layout/' . $layout)

@section('subhead')
<title>Add Puja</title>
@endsection

@section('subcontent')
<style>
    .puja-selector-input {
        width: 100%;
        height: 100%;
        opacity: 0;
        overflow: hidden;
        position: absolute;
        left: 0;
        top: 0;
        cursor: pointer;
        z-index: 10;
    }

    .puja-drop-zone {
        position: relative;
        border: 2px dashed #cbd5e1;
        background-color: #f8fafc;
        border-radius: 8px;
        padding: 24px;
        text-align: center;
        transition: border-color 0.2s, background-color 0.2s;
        cursor: pointer;
    }

    .puja-drop-zone:hover {
        border-color: #4045ba;
        background-color: #f1f5f9;
    }

    .puja-gallery-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        margin-top: 16px;
    }

    .puja-gallery-item {
        width: 120px;
        height: 120px;
        position: relative;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        background-color: #f1f5f9;
    }

    .puja-gallery-remove {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background-color: rgba(239, 68, 68, 0.9);
        position: absolute;
        top: 6px;
        right: 6px;
        text-align: center;
        line-height: 18px;
        z-index: 20;
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .puja-gallery-remove:hover {
        background-color: rgb(220, 38, 38);
    }

    .puja-gallery-remove:after {
        content: "✖";
        font-size: 11px;
        color: white;
        font-weight: bold;
    }

    .puja-gallery-bg {
        width: 100%;
        height: 100%;
        background-repeat: no-repeat;
        background-position: center;
        background-size: cover;
    }
</style>

<div class="grid grid-cols-12 gap-6 mt-5">
    <div class="intro-y col-span-12 mt-2">
        <div class="intro-y box">
            <div
                class="flex flex-col sm:flex-row items-center p-5 border-b border-slate-200/60 dark:border-darkmode-400">
                <h2 class="font-medium text-base mr-auto">Add Puja</h2>
            </div>
            <div class="p-5">
                <form action="{{ isset($puja) ? route('puja.update', $puja->id) : route('puja.store') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <!-- Title and Subtitle (Col-6 Col-6) -->
                    <div class="grid grid-cols-12 gap-6">
                        <div class="col-span-12 sm:col-span-6">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="title" class="form-control w-full" required
                                value="{{old('title',@$puja->puja_title)}}" placeholder="Enter title">
                        </div>
                        <div class="col-span-12 sm:col-span-6">
                            <label for="subtitle" class="form-label">Subtitle <span class="text-danger">*</span></label>
                            <input type="text" name="subtitle" id="subtitle" class="form-control w-full" required
                                value="{{old('subtitle',@$puja->puja_subtitle)}}" placeholder="Enter subtitle">
                        </div>
                    </div>
                    <div class="grid grid-cols-12 gap-6 mt-5">
                        <div class="intro-y col-span-6 md:col-span-6">
                            <label id="input-group" class="form-label">Start Date Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" placeholder="FromTime"
                                name="puja_start_datetime" id="puja_start_datetime" aria-describedby="input-group-4"
                                value="{{ isset($puja) ? date('Y-m-d\TH:i', strtotime($puja->puja_start_datetime)) : old('puja_start_datetime') }}">
                        </div>

                        <div class="intro-y col-span-6 md:col-span-6">
                            <label id="input-group" class="form-label">Puja Duration (in minutes) <span class="text-danger">*</span></label>
                            <input type="text" name="puja_duration" id="puja_duration" class="form-control w-full"
                                value="{{old('puja_duration',@$puja->puja_duration)}}" placeholder="120" required>
                        </div>

                    </div>

                    <!-- Category and Place (Col-6 Col-6) -->
                    <div class="grid grid-cols-12 gap-6 mt-5">
                        <div class="col-span-12 sm:col-span-4">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" id="category_id" class="form-select w-full" required>
                                <option value="">Select Category</option>
                                @foreach ($pujaCategory as $pujacat)
                                <option value="{{ $pujacat->id }}" {{ (isset($puja) && $puja->category_id == $pujacat->id) ? 'selected' : (old('category_id') == $pujacat->id ? 'selected' : '')}}>
                                    {{ $pujacat->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-span-12 sm:col-span-4">
                            <label for="place" class="form-label">Place <span class="text-danger">*</span></label>
                            <input type="text" name="place" id="place" value="{{old('place',@$puja->puja_place)}}"
                                class="form-control w-full" placeholder="Enter place" required>
                        </div>
                        <div class="col-span-12 sm:col-span-4">
                            <label for="package_id" class="form-label">Select Package <span class="text-danger">*</span></label>
                            <select name="package_id[]" id="package_id" class="form-control select2" multiple required>
                                @foreach ($packages as $package)
                                <option value="{{ $package->id }}"
                                    @if (is_array(old('package_id')) && in_array($package->id, old('package_id')))
                                    selected
                                    @elseif (isset($puja) && is_array($puja->package_id) && in_array($package->id, $puja->package_id))
                                    selected
                                    @endif>
                                    {{ $package->title }} - {{ $package->package_price }}
                                </option>
                                @endforeach
                            </select>

                        </div>
                    </div>
                    <!-- Description (Full Width) -->
                    <div class="mt-5">
                        <label for="description" class="form-label">About Puja <span class="text-danger">*</span></label>
                        <textarea name="description" id="description" class="form-control w-full" required
                            placeholder="Enter description">{{old('description',@$puja->long_description)}}</textarea>
                    </div>
                    <!-- Puja Benefits Section -->
                    <div class="border border-gray-300 p-4 rounded mt-5">
                        <h3 class="text-lg font-medium">Puja Benefits</h3>
                        <button type="button" id="add-benefit" class="btn btn-outline-primary mt-3">+ Add
                            Benefit</button>

                        <div id="puja-benefits" class="grid grid-cols-12 gap-6 mt-3">

                            @if(isset($puja) && !empty($puja->puja_benefits) && is_array($puja->puja_benefits))
                            @foreach ($puja->puja_benefits as $benkey => $ben)

                            <div class="col-span-12 sm:col-span-6 relative border border-gray-300 p-4 rounded mt-3">
                                <h3 class="font-bold mb-2"> Benefit </h3>

                                <input type="text" name="benefit_title[]"
                                    class="form-control w-full mb-2 border border-gray-300 p-2 rounded"
                                    value="{{ $ben['title'] ?? '' }}" placeholder="Enter benefit title" required>

                                <textarea name="benefit_description[]"
                                    class="form-control w-full border border-gray-300 p-2 rounded"
                                    placeholder="Enter benefit description" required>{{ $ben['description'] ?? '' }}</textarea>

                                <button type="button"
                                    class="absolute top-0 right-0 bg-red-500 text-danger border border-gray-800 rounded-full w-5 h-5 flex items-center justify-center cursor-pointer text-sm badge-button shadow-md">×</button>
                            </div>
                            @endforeach
                            @endif
                        </div>
                    </div>
                    <!-- end sections -->
                    <div class="puja-img-area mt-5">
                        <label class="form-label font-medium text-slate-600 dark:text-slate-400">Upload Images <span class="text-danger">*</span></label>
                        <div class="puja-drop-zone mt-2">
                            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#4045ba" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mx-auto mb-2">
                                <rect width="18" height="18" x="3" y="3" rx="2" ry="2" />
                                <circle cx="9" cy="9" r="2" />
                                <path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
                            </svg>
                            <span class="font-semibold text-slate-700 block">Click to Upload Images or Drag & Drop</span>
                            <span class="text-xs text-slate-500 mt-1 block">Upload up to 20 images</span>
                            <input type="file" multiple="" name="puja_images[]" data-max_length="20" class="puja-selector-input">
                        </div>
                        <div class="puja-gallery-grid">
                            @if(isset($puja) && !empty($puja->puja_images) && is_array($puja->puja_images))
                            @foreach ($puja->puja_images as $imgkey => $img)
                            <div class="puja-gallery-item">
                                <div style="background-image: url('{{ Str::startsWith($img, ['http://','https://']) ? $img : asset($img) }}');" class="puja-gallery-bg"
                                    data-file="{{ $img }}">
                                    <input type="file" name="old_images[]" multiple style="display:none;">
                                    <input type="hidden" name="existing_images[]" value="{{ $img }}">
                                    <div class="puja-gallery-remove" name="removed_images"></div>
                                </div>
                            </div>
                            @endforeach
                            @endif
                        </div>
                    </div>
                    <!-- Submit Button -->
                    <div class="mt-5">
                        <button type="submit" class="btn btn-primary">Submit</button>
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
    $(document).ready(function() {
        jQuery('.select2').select2({
            allowClear: true,
            tokenSeparators: [',', ' ']
        });
    });

    jQuery(document).ready(function() {
        ImgUpload();
    });

    function ImgUpload() {
        var imgWrap = "";
        var imgArray = [];

        $('.puja-selector-input').each(function() {
            $(this).on('change', function(e) {
                imgWrap = $(this).closest('.puja-img-area').find('.puja-gallery-grid');
                var maxLength = $(this).attr('data-max_length');

                var files = e.target.files;
                var filesArr = Array.prototype.slice.call(files);
                var iterator = 0;
                filesArr.forEach(function(f) {

                    if (!f.type.match('image.*')) {
                        return;
                    }

                    if (imgArray.length >= maxLength) {
                        return false;
                    } else {
                        imgArray.push(f);

                        var reader = new FileReader();
                        reader.onload = function(e) {
                            var html = "<div class='puja-gallery-item'><div style='background-image: url(" + e.target.result + ")' data-file='" + f.name + "' class='puja-gallery-bg'><div class='puja-gallery-remove'></div></div></div>";
                            imgWrap.append(html);
                            iterator++;
                        }
                        reader.readAsDataURL(f);
                    }
                });
            });
        });

        $('body').on('click', ".puja-gallery-remove", function() {
            var file = $(this).parent().data("file");
            imgArray = imgArray.filter(f => f.name !== file);
            $(this).closest('.puja-gallery-item').remove();
        });

        // Optionally reset imgArray on form submit
        $('form').on('submit', function() {
            imgArray = [];
        });
    }



    document.addEventListener('DOMContentLoaded', function() {
        let benefitCount = 0; // Initialize counter

        document.getElementById('add-benefit').addEventListener('click', function() {
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
            removeButton.addEventListener('click', function() {
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



@endsection
