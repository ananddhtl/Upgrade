@extends('backend.layouts.app')
@section('content')

<?php
use App\Models\Category;
use App\Models\Product;

?>

<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="row">
	<div class="col-xl-10 mx-auto">
		<h6 class="fw-600">{{ translate('Home Page Settings') }}</h6>

		{{-- Home Slider --}}
		<div class="card">
			<div class="card-header">
				<h6 class="mb-0">{{ translate('Home Slider') }}</h6>
			</div>
			<div class="card-body">
				<div class="alert alert-info">
					{{ translate('We have limited banner height to maintain UI. We had to crop from both left & right side in view for different devices to make it responsive. Before designing banner keep these points in mind.') }}
				</div>

				
				<form action="{{ route('business_settings.update') }}" method="POST" enctype="multipart/form-data">
					@csrf
					<div class="form-group"> 
						 
						<div class="home-slider-target" id="home_slider_target">
							<input type="hidden" name="types[]" value="home_slider_images">
							<input type="hidden" name="types[]" value="home_slider_links"> 
							<input type="hidden" name="types[]" value="home_slider_type">
							<input type="hidden" name="types[]" value="home_slider_item_id">
							@if (get_setting('home_slider_images') != null) 
								@foreach (json_decode(get_setting('home_slider_images'), true) as $key => $value)
									@php 
										
										
										if(json_decode(get_setting('home_slider_type'), true)[$key]=='2'){
										 if(!(json_decode(get_setting('home_slider_item_id'), true)[$key]=="" || json_decode(get_setting('home_slider_item_id'), true)[$key]==null)){
										    $selectedProduct = Product::where('id', json_decode(get_setting('home_slider_item_id'), true)[$key])->first();
											
											$selectedProductCategory = Category::where('id', $selectedProduct->category_id)->first();
											$products = Product::where('category_id', $selectedProductCategory->id)->get();
										 }
											
										}
									@endphp

									<div class="row gutters-5">
										<h6>Slider {{$key+1}}</h6>
										<div class="d-flex col-md-12">
											<div class="col-md-10">
												<div class="row mb-2">
													<div class="col-md-5">
														<label for="category" class="form-label">Slider Type</label>
														<input type="hidden" name="types[]" value="home_slider_type">
														<select class="form-control" name="home_slider_type[]" onchange="sliderTypeChanged('<?php echo $key;?>')" id="home_slider_category_type{{$key}}">
															<option hidden>Choose Type</option>
															
															<option value="1" @if(json_decode(get_setting('home_slider_type'), true)[$key]=='1') selected @endif>Category</option>
															<option value="2" @if(json_decode(get_setting('home_slider_type'), true)[$key]=='2') selected @endif>Product</option>
															
														</select>
													</div>
	
													
	
													
													
												</div>
	
												<div class="row mb-2" id="slidercategorywrapperone{{$key}}" @if(json_decode(get_setting('home_slider_type'), true)[$key]=='2'||json_decode(get_setting('home_slider_type'), true)[$key]==null) style="display:none;" @endif>
													<div class="col-md-5">
														<label for="category" class="form-label">Slider Category</label>
														<input type="hidden" name="types[]" value="home_slider_item_id">
														<select class="form-control" name="home_slider_item_id[]" >
															<option hidden>Choose Category</option>
	
															@foreach($categories as $category)
															<option value="{{$category->id}}" @if(json_decode(get_setting('home_slider_item_id'), true)[$key]==$category->id) selected @endif>{{$category->name}}</option>
															@endforeach
														</select>
													</div>
	
													
	
													<div class="col-md-7">
														<label for="category" class="form-label">Slider Product</label>
														
														<select class="form-control"  readonly>
															<option value='' hidden>Choose Product</option>
	
															
															
															
														</select>
													</div>
													
												</div>
												<div class="row mb-2" id="slidercategorywrappertwo{{$key}}" @if(json_decode(get_setting('home_slider_type'), true)[$key]=='1'||json_decode(get_setting('home_slider_type'), true)[$key]==null) style="display:none;" @endif>
													<div class="col-md-5">
														<label for="category" class="form-label">Slider Category</label>
														
														<select class="form-control" onchange="setSliderCategoryProduct('<?php echo $key;?>')" id="home_slider_category_id{{$key}}">
															<option hidden>Choose Category</option>
															@if(json_decode(get_setting('home_slider_type'), true)[$key]=='2')
															@foreach($categories as $category)
															<option value="{{$category->id}}" @if($selectedProductCategory->id==$category->id) selected @endif>{{$category->name}}</option>
															@endforeach
															@else
															@foreach($categories as $category)
															<option value="{{$category->id}}">{{$category->name}}</option>
															@endforeach
															@endif
														</select>
													</div>
	
													
	
													<div class="col-md-7">
														<label for="category" class="form-label">Slider Product</label>
														<input type="hidden" name="types[]" value="home_slider_item_id_category">
														<select class="form-control" name="home_slider_item_id_category[]" id="home_slider_item{{$key}}">
															<option value='' hidden>Choose Product</option>
															@if(json_decode(get_setting('home_slider_type'), true)[$key]=='2')
															@foreach($products as $product)
															<option value="{{$product->id}}" @if(json_decode(get_setting('home_slider_item_id'), true)[$key]==$product->id) selected @endif>{{$product->name}}</option>
															@endforeach
															@endif
															
															
															
														</select>
													</div>
													
												</div>
												<div class="row">
													<div class="col-md-5">
														<div class="form-group">
															<div class="input-group" data-toggle="aizuploader" data-type="image">
																<div class="input-group-prepend">
																	<div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
																</div>
																<div class="form-control file-amount">{{ translate('Choose File') }}</div>
																<input type="hidden" name="types[]" value="home_slider_images">
																<input type="hidden" name="home_slider_images[]" class="selected-files" value="{{ json_decode(get_setting('home_slider_images'), true)[$key] }}">
															</div>
															<div class="file-preview box sm">
															</div>
														</div>
													</div>
													<div class="col-md">
														<div class="form-group">
															<input type="hidden" name="types[]" value="home_slider_links">
															<input type="text" class="form-control" placeholder="http://" name="home_slider_links[]" value="{{ json_decode(get_setting('home_slider_links'), true)[$key] }}">
														</div>
													</div>
												</div>
												
											</div>
											<div class="col-md-2">
												<div class="form-group">
													<button type="button" class="mt-1 btn btn-icon btn-circle btn-sm btn-soft-danger" data-toggle="remove-parent" data-parent=".row">
														<i class="las la-times"></i>
													</button>
												</div>
											</div>
										</div>
									</div>
								@endforeach
							@endif
						</div>
						<button
							type="button"
							class="btn btn-soft-secondary btn-sm"
							id="addSliderImage"
							>
							{{ translate('Add New') }}
						</button>
					</div>
					<div class="text-right">
						<button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
					</div>
				</form>
			</div>
		</div>

		{{-- Home Banner 1 --}}
		<div class="card">
			<div class="card-header">
				<h6 class="mb-0">{{ translate('Home Banner 1 (Max 3)') }}</h6>
			</div>
			<div class="card-body">
				<form action="{{ route('business_settings.update') }}" method="POST" enctype="multipart/form-data">
					@csrf
					<div class="form-group">
						<label>{{ translate('Banner & Links') }}</label>
						<div class="home-banner1-target">
							<input type="hidden" name="types[]" value="home_banner1_images">
							<input type="hidden" name="types[]" value="home_banner1_links">
							@if (get_setting('home_banner1_images') != null)
								@foreach (json_decode(get_setting('home_banner1_images'), true) as $key => $value)
									<div class="row gutters-5">
										<div class="col-md-5">
											<div class="form-group">
												<div class="input-group" data-toggle="aizuploader" data-type="image">
					                                <div class="input-group-prepend">
					                                    <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
					                                </div>
					                                <div class="form-control file-amount">{{ translate('Choose File') }}</div>
													<input type="hidden" name="types[]" value="home_banner1_images">
					                                <input type="hidden" name="home_banner1_images[]" class="selected-files" value="{{ json_decode(get_setting('home_banner1_images'), true)[$key] }}">
					                            </div>
					                            <div class="file-preview box sm">
					                            </div>
				                            </div>
										</div>
										<div class="col-md">
											<div class="form-group">
												<input type="hidden" name="types[]" value="home_banner1_links">
												<input type="text" class="form-control" placeholder="http://" name="home_banner1_links[]" value="{{ json_decode(get_setting('home_banner1_links'), true)[$key] }}">
											</div>
										</div>
										<div class="col-md-auto">
											<div class="form-group">
												<button type="button" class="mt-1 btn btn-icon btn-circle btn-sm btn-soft-danger" data-toggle="remove-parent" data-parent=".row">
													<i class="las la-times"></i>
												</button>
											</div>
										</div>
									</div>
								@endforeach
							@endif
						</div>
						<button
							type="button"
							class="btn btn-soft-secondary btn-sm"
							data-toggle="add-more"
							data-content='
							<div class="row gutters-5">
								<div class="col-md-5">
									<div class="form-group">
										<div class="input-group" data-toggle="aizuploader" data-type="image">
											<div class="input-group-prepend">
												<div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
											</div>
											<div class="form-control file-amount">{{ translate('Choose File') }}</div>
											<input type="hidden" name="types[]" value="home_banner1_images">
											<input type="hidden" name="home_banner1_images[]" class="selected-files">
										</div>
										<div class="file-preview box sm">
										</div>
									</div>
								</div>
								<div class="col-md">
									<div class="form-group">
										<input type="hidden" name="types[]" value="home_banner1_links">
										<input type="text" class="form-control" placeholder="http://" name="home_banner1_links[]">
									</div>
								</div>
								<div class="col-md-auto">
									<div class="form-group">
										<button type="button" class="mt-1 btn btn-icon btn-circle btn-sm btn-soft-danger" data-toggle="remove-parent" data-parent=".row">
											<i class="las la-times"></i>
										</button>
									</div>
								</div>
							</div>'
							data-target=".home-banner1-target">
							{{ translate('Add New') }}
						</button>
					</div>
					<div class="text-right">
						<button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
					</div>
				</form>
			</div>
		</div>

		{{-- Home Banner 2 --}}
		<div class="card">
			<div class="card-header">
				<h6 class="mb-0">{{ translate('Home Banner 2 (Max 3)') }}</h6>
			</div>
			<div class="card-body">
				<form action="{{ route('business_settings.update') }}" method="POST" enctype="multipart/form-data">
					@csrf
					<div class="form-group">
						<label>{{ translate('Banner & Links') }}</label>
						<div class="home-banner2-target">
							<input type="hidden" name="types[]" value="home_banner2_images">
							<input type="hidden" name="types[]" value="home_banner2_links">
							@if (get_setting('home_banner2_images') != null)
								@foreach (json_decode(get_setting('home_banner2_images'), true) as $key => $value)
									<div class="row gutters-5">
										<div class="col-md-5">
											<div class="form-group">
												<div class="input-group" data-toggle="aizuploader" data-type="image">
					                                <div class="input-group-prepend">
					                                    <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
					                                </div>
					                                <div class="form-control file-amount">{{ translate('Choose File') }}</div>
													<input type="hidden" name="types[]" value="home_banner2_images">
					                                <input type="hidden" name="home_banner2_images[]" class="selected-files" value="{{ json_decode(get_setting('home_banner2_images'), true)[$key] }}">
					                            </div>
					                            <div class="file-preview box sm">
					                            </div>
				                            </div>
										</div>
										<div class="col-md">
											<div class="form-group">
												<input type="hidden" name="types[]" value="home_banner2_links">
												<input type="text" class="form-control" placeholder="http://" name="home_banner2_links[]" value="{{ json_decode(get_setting('home_banner2_links'), true)[$key] }}">
											</div>
										</div>
										<div class="col-md-auto">
											<div class="form-group">
												<button type="button" class="mt-1 btn btn-icon btn-circle btn-sm btn-soft-danger" data-toggle="remove-parent" data-parent=".row">
													<i class="las la-times"></i>
												</button>
											</div>
										</div>
									</div>
								@endforeach
							@endif
						</div>
						<button
							type="button"
							class="btn btn-soft-secondary btn-sm"
							data-toggle="add-more"
							data-content='
							<div class="row gutters-5">
								<div class="col-md-5">
									<div class="form-group">
										<div class="input-group" data-toggle="aizuploader" data-type="image">
											<div class="input-group-prepend">
												<div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
											</div>
											<div class="form-control file-amount">{{ translate('Choose File') }}</div>
											<input type="hidden" name="types[]" value="home_banner2_images">
											<input type="hidden" name="home_banner2_images[]" class="selected-files">
										</div>
										<div class="file-preview box sm">
										</div>
									</div>
								</div>
								<div class="col-md">
									<div class="form-group">
										<input type="hidden" name="types[]" value="home_banner2_links">
										<input type="text" class="form-control" placeholder="http://" name="home_banner2_links[]">
									</div>
								</div>
								<div class="col-md-auto">
									<div class="form-group">
										<button type="button" class="mt-1 btn btn-icon btn-circle btn-sm btn-soft-danger" data-toggle="remove-parent" data-parent=".row">
											<i class="las la-times"></i>
										</button>
									</div>
								</div>
							</div>'
							data-target=".home-banner2-target">
							{{ translate('Add New') }}
						</button>
					</div>
					<div class="text-right">
						<button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
					</div>
				</form>
			</div>
		</div>

		{{-- Home categories--}}
		<div class="card">
			<div class="card-header">
				<h6 class="mb-0">{{ translate('Home Categories') }}</h6>
			</div>
			<div class="card-body">
				<form action="{{ route('business_settings.update') }}" method="POST" enctype="multipart/form-data">
					@csrf
					<div class="form-group">
						<label>{{ translate('Categories') }}</label>
						<div class="home-categories-target">
							<input type="hidden" name="types[]" value="home_categories">
							@if (get_setting('home_categories') != null)
								@foreach (json_decode(get_setting('home_categories'), true) as $key => $value)
									<div class="row gutters-5">
										<div class="col">
											<div class="form-group">
												<select class="form-control aiz-selectpicker" name="home_categories[]" data-live-search="true" data-selected={{ $value }} required>
													@foreach (\App\Models\Category::where('parent_id', 0)->with('childrenCategories')->get() as $category)
														<option value="{{ $category->id }}">{{ $category->getTranslation('name') }}</option>
														@foreach ($category->childrenCategories as $childCategory)
															@include('categories.child_category', ['child_category' => $childCategory])
														@endforeach
													@endforeach
					                            </select>
											</div>
										</div>
										<div class="col-auto">
											<button type="button" class="mt-1 btn btn-icon btn-circle btn-sm btn-soft-danger" data-toggle="remove-parent" data-parent=".row">
												<i class="las la-times"></i>
											</button>
										</div>
									</div>
								@endforeach
							@endif
						</div>
						<button
							type="button"
							class="btn btn-soft-secondary btn-sm"
							data-toggle="add-more"
							data-content='<div class="row gutters-5">
								<div class="col">
									<div class="form-group">
										<select class="form-control aiz-selectpicker" name="home_categories[]" data-live-search="true" required>
											@foreach (\App\Models\Category::all() as $key => $category)
												<option value="{{ $category->id }}">{{ $category->getTranslation('name') }}</option>
											@endforeach
										</select>
									</div>
								</div>
								<div class="col-auto">
									<button type="button" class="mt-1 btn btn-icon btn-circle btn-sm btn-soft-danger" data-toggle="remove-parent" data-parent=".row">
										<i class="las la-times"></i>
									</button>
								</div>
							</div>'
							data-target=".home-categories-target">
							{{ translate('Add New') }}
						</button>
					</div>
					<div class="text-right">
						<button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
					</div>
				</form>
			</div>
		</div>


		{{-- Home Banner 3 --}}
		<div class="card">
			<div class="card-header">
				<h6 class="mb-0">{{ translate('Home Banner 3 (Max 3)') }}</h6>
			</div>
			<div class="card-body">
				<form action="{{ route('business_settings.update') }}" method="POST" enctype="multipart/form-data">
					@csrf
					<div class="form-group">
						<label>{{ translate('Banner & Links') }}</label>
						<div class="home-banner3-target">
							<input type="hidden" name="types[]" value="home_banner3_images">
							<input type="hidden" name="types[]" value="home_banner3_links">
							@if (get_setting('home_banner3_images') != null)
								@foreach (json_decode(get_setting('home_banner3_images'), true) as $key => $value)
									<div class="row gutters-5">
										<div class="col-md-5">
											<div class="form-group">
												<div class="input-group" data-toggle="aizuploader" data-type="image">
					                                <div class="input-group-prepend">
					                                    <div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
					                                </div>
					                                <div class="form-control file-amount">{{ translate('Choose File') }}</div>
													<input type="hidden" name="types[]" value="home_banner3_images">
					                                <input type="hidden" name="home_banner3_images[]" class="selected-files" value="{{ json_decode(get_setting('home_banner3_images'), true)[$key] }}">
					                            </div>
					                            <div class="file-preview box sm">
					                            </div>
				                            </div>
										</div>
										<div class="col-md">
											<div class="form-group">
												<input type="hidden" name="types[]" value="home_banner3_links">
												<input type="text" class="form-control" placeholder="http://" name="home_banner3_links[]" value="{{ json_decode(get_setting('home_banner3_links'), true)[$key] }}">
											</div>
										</div>
										<div class="col-md-auto">
											<div class="form-group">
												<button type="button" class="mt-1 btn btn-icon btn-circle btn-sm btn-soft-danger" data-toggle="remove-parent" data-parent=".row">
													<i class="las la-times"></i>
												</button>
											</div>
										</div>
									</div>
								@endforeach
							@endif
						</div>
						<button
							type="button"
							class="btn btn-soft-secondary btn-sm"
							data-toggle="add-more"
							data-content='
							<div class="row gutters-5">
								<div class="col-md-5">
									<div class="form-group">
										<div class="input-group" data-toggle="aizuploader" data-type="image">
											<div class="input-group-prepend">
												<div class="input-group-text bg-soft-secondary font-weight-medium">{{ translate('Browse')}}</div>
											</div>
											<div class="form-control file-amount">{{ translate('Choose File') }}</div>
											<input type="hidden" name="types[]" value="home_banner3_images">
											<input type="hidden" name="home_banner3_images[]" class="selected-files">
										</div>
										<div class="file-preview box sm">
										</div>
									</div>
								</div>
								<div class="col-md">
									<div class="form-group">
										<input type="hidden" name="types[]" value="home_banner3_links">
										<input type="text" class="form-control" placeholder="http://" name="home_banner3_links[]">
									</div>
								</div>
								<div class="col-md-auto">
									<div class="form-group">
										<button type="button" class="mt-1 btn btn-icon btn-circle btn-sm btn-soft-danger" data-toggle="remove-parent" data-parent=".row">
											<i class="las la-times"></i>
										</button>
									</div>
								</div>
							</div>'
							data-target=".home-banner3-target">
							{{ translate('Add New') }}
						</button>
					</div>
					<div class="text-right">
						<button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
					</div>
				</form>
			</div>
		</div>

		{{-- Top 10 --}}
		<div class="card">
			<div class="card-header">
				<h6 class="mb-0">{{ translate('Top 10') }}</h6>
			</div>
			<div class="card-body">
				<form action="{{ route('business_settings.update') }}" method="POST" enctype="multipart/form-data">
					@csrf
					<div class="form-group row">
						<label class="col-md-2 col-from-label">{{translate('Top Categories (Max 10)')}}</label>
						<div class="col-md-10">
							<input type="hidden" name="types[]" value="top10_categories">
							<select name="top10_categories[]" class="form-control aiz-selectpicker" multiple data-max-options="10" data-live-search="true" data-selected="{{ get_setting('top10_categories') }}">
								@foreach (\App\Models\Category::where('parent_id', 0)->with('childrenCategories')->get() as $category)
									<option value="{{ $category->id }}">{{ $category->getTranslation('name') }}</option>
									@foreach ($category->childrenCategories as $childCategory)
										@include('categories.child_category', ['child_category' => $childCategory])
									@endforeach
								@endforeach
							</select>
						</div>
					</div>
					<div class="form-group row">
						<label class="col-md-2 col-from-label">{{translate('Top Brands (Max 10)')}}</label>
						<div class="col-md-10">
							<input type="hidden" name="types[]" value="top10_brands">
							<select name="top10_brands[]" class="form-control aiz-selectpicker" multiple data-max-options="10" data-live-search="true" data-selected="{{ get_setting('top10_brands') }}">
								@foreach (\App\Models\Brand::all() as $key => $brand)
									<option value="{{ $brand->id }}">{{ $brand->getTranslation('name') }}</option>
								@endforeach
							</select>
						</div>
					</div>
					<div class="text-right">
						<button type="submit" class="btn btn-primary">{{ translate('Update') }}</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

@endsection

@section('script')
    <script type="text/javascript">
		$(document).ready(function(){
		    AIZ.plugins.bootstrapSelect('refresh');
		});


		function sliderTypeChanged(i){
			var categoryID = $('#home_slider_category_type'+i).val();
			// alert(categoryID);
			if(categoryID==1){
				$('#slidercategorywrapperone'+i).show();
				$('#slidercategorywrappertwo'+i).hide();
				
			} else if(categoryID==2){
				$('#slidercategorywrapperone'+i).hide();
				$('#slidercategorywrappertwo'+i).show();
				
			} else{
				$('#slidercategorywrapperone'+i).hide();
				$('#slidercategorywrappertwo'+i).hide();
			}

			// var allproductitem = "#home_slider_item"+i;
			// $(allproductitem).empty();
			// $(allproductitem).append('<option value="1">Changed</option>');
		}


		function setSliderCategoryProduct(index){
		    
			var categoryId = $('#home_slider_category_id'+index).val();

			var token = $('meta[name="csrf-token"]').attr('content');
			if(categoryId) {
				$.ajax({
				    
					url: '/products/category/'+categoryId,
					type: "GET",
					dataType: "json",
					success:function(response)
					{
						
						if(response.data){
    						$('#home_slider_item'+index).empty();
    						$('#home_slider_item'+index).append('<option hidden>Choose Product</option>'); 
    				
    						$.each(response.data, function(key, item){
                                
    							$('#home_slider_item'+index).append('<option value="'+ item.id +'">' + item.name+ '</option>');
    						});
    					}else{
    						$('#home_slider_item'+index).empty();
    					}
					}
				});
			}
		}


		$("#addSliderImage").click(function(){
			$.ajax({
				url: '/admin/business-settings/addSliderImage',
				type: "POST",
				data : {"_token":"{{ csrf_token() }}"},
				success:function(data)
				{
					location.reload();
					
				}
				
			});
		});

		

		
    </script>
@endsection
