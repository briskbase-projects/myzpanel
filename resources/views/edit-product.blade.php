@extends('layouts.app')
@push('style')
<style>
   .expended_html {
   border-top: 2px solid #d9d9d9;
   margin-bottom: 20px;
   padding-top: 20px;
   }

   /* Required Attributes Styling */
   .required-attr-section {
     background: #f8f9fa;
     border: 1px solid #dee2e6;
     border-radius: 8px;
     padding: 15px;
     margin-bottom: 15px;
   }

   .required-attr-section h6 {
     color: #495057;
     font-weight: 600;
     margin-bottom: 10px;
     padding-bottom: 8px;
     border-bottom: 2px solid #007bff;
     font-size: 14px;
   }

   .required-attr-section .expended_html {
     background: white;
     border-radius: 5px;
     padding: 10px;
     margin-bottom: 8px;
     border-left: 3px solid #007bff;
   }

   .percentage-total {
     text-align: center;
     border-radius: 5px;
     font-size: 13px;
     padding: 8px 10px;
     margin: 8px 0;
   }

   .percentage-total.alert-success {
     background-color: #d4edda;
     border-color: #c3e6cb;
     color: #155724;
   }

   .percentage-total.alert-danger {
     background-color: #f8d7da;
     border-color: #f5c6cb;
     color: #721c24;
   }

   .percentage-total.alert-warning {
     background-color: #fff3cd;
     border-color: #ffeaa7;
     color: #856404;
   }

   .material-select {
     border: 1px solid #ced4da;
     font-size: 13px;
     padding: 6px 10px;
   }

   .material-percentage {
     border: 1px solid #ced4da;
     font-size: 13px;
     padding: 6px 10px;
   }

   .material-select:focus,
   .material-percentage:focus {
     border-color: #007bff;
     box-shadow: 0 0 0 0.15rem rgba(0,123,255,.15);
   }

   .required-attr-section .form-group {
     margin-bottom: 8px;
   }

   .required-attr-section label {
     font-size: 12px;
     font-weight: 500;
     margin-bottom: 4px;
   }

   .required-attr-section .btn-sm {
     font-size: 12px;
     padding: 4px 10px;
   }

   .required-attr-section .text-center {
     margin-top: 8px;
   }
   .remove-variant {
    color: #Fff;
    text-decoration: underline;
    position: absolute;
    top: 17px;
    right: 10px;
    font-size: 16px;
  }
    .card .btn.btn-link {
    display: block;
    width: 100%;
    text-align: left;
    color: #fff;
    }
    
</style>
@endpush
@section('content')
@php 
  $countLoop = 0;
@endphp
@php
    $channels = config('channels');
    $filteredChannels = collect($channels)->filter(function ($channel) {
        return $channel['status'];
    });
    $countries = $filteredChannels->unique('country');
@endphp

<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.6-rc.0/css/select2.min.css" rel="stylesheet" />
<div class="container">
  <form action="{{route('save-product')}}" method="post" enctype="multipart/form-data" onsubmit="return false">
  {{csrf_field()}}
  <div id="accordion">
    <div class="card">
      <div class="card-header" id="headingOne">
        <h5 class="mb-0">
          <button class="btn btn-link" data-toggle="collapse" data-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
            Article
          </button>
        </h5>
      </div>

      <div id="collapseOne" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
        <div class="card-body">
          <input type="hidden" name="id[0]" value="{{isset($detail)?$detail->id:''}}">
          <div class="row">
            <div class="col-md-2 form-group">
                <label>Outline</label>
                <select name="outline" id="outline" class="form-control">
                  @foreach($outlines->items as $outline)
                  <option value="{{ $outline->label }}" {{isset($detail) && $detail->outline == $outline->label?'selected':''}}>{{ $outline->name->en }}</option>
                  @endforeach
                </select>
            </div>
            <div class="col-md-3 form-group">
                <label>Title</label>
                <input type="text" class="form-control" name="title" required="" value="{{isset($detail)?$detail->title:''}}">
            </div>
            <div class="col-md-3 form-group">
                <label>Tags</label>
                <input type="text" class="form-control" name="tags" required="" value="{{isset($detail)?$detail->tags:''}}">
            </div>
            <div class="col-md-2 form-group">
                <label>Brand Code</label>
                <select name="brand_code" class="form-control">
                  <option value="">Select</option>
                  @if(!empty($brand_codes))
                  @foreach($brand_codes->items as $bc)
                  <option value="{{$bc->label}}" {{isset($detail) && $detail->brand_code == $bc->label?'selected':''}}>{{$bc->name->en}}</option>
                  @endforeach
                  @endif
                </select>
            </div>
            <div class="col-md-2 form-group">
                <label>Product Model ID</label>
                <input type="text" class="form-control" name="merchant_product_model_id" value="{{isset($detail)?$detail->merchant_product_model_id:''}}">
            </div>
          </div>
          <div class="row">
            <div class="col-md-4 form-group">
                <label>Target Gender Code </label>
                <select name="target_genders[]" class="form-control myselect" multiple="">
                  <option value="">Select</option>
                  @if(!empty($target_genders))
                  @foreach($target_genders->items as $tg)
                  <option value="{{$tg->label}}" {{isset($detail) && !is_null($detail->target_genders) && in_array($tg->label, $detail->target_genders)?'selected':''}}>{{$tg->name->en}}</option>
                  @endforeach
                  @endif
                </select>
            </div>
            <div class="col-md-4 form-group">
                <label>Target Age Group Code </label>
                <select name="target_age_groups[]" class="form-control myselect" multiple="">
                  <option value="">Select</option>
                  @if(!empty($target_age_groups))
                  @foreach($target_age_groups->items as $tag)
                  <option value="{{$tag->label}}" {{isset($detail) && !is_null($detail->target_age_groups) &&  in_array($tag->label, $detail->target_age_groups)?'selected':''}}>{{$tag->name->en}}</option>
                  @endforeach
                  @endif
                </select>
            </div>
            <div class="col-md-4 form-group">
                <label>Size Group </label>
                <select name="size_group" class="form-control" >
                  <option value="">Select</option>
                  @if(!empty($size_group))
                  @foreach($size_group->items as $tag)
                  <option value="{{$tag->label}}" {{isset($detail) && !is_null($detail->size_group) &&  $tag->label == $detail->size_group?'selected':''}} >{{$tag->_meta->descriptions->en}}[{{$tag->label}}]</option>
                  @endforeach
                  @endif
                </select>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="card" id="clone">
      <div class="card-header" id="headingTwo">
        <h5 class="mb-0">
          <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
            Article Variant 
          </button>
          <a href="javascript:;" class="remove-variant">Remove Variant</a>
        </h5>
      </div>
      <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordion">
        <div class="card-body">
          <div class="row">
            <div class="col-md-12">
                <div class="row">
                  <div class="col-md-2 form-group">
                    <label>Primary Color Code</label>
                    <select name="color_code[0]" class="form-control">
                      <option value="">Select</option>
                      @if(!empty($color_code))
                      @foreach($color_code->items as $cc)
                      <option value="{{$cc->label}}" {{isset($detail) && $detail->color_code == $cc->label?'selected':''}}>{{$cc->value->localized->en}}</option>
                      @endforeach
                      @endif
                    </select>
                </div>
                <div class="col-md-2 form-group">
                    <label>Article Variant ID</label>
                    <input type="text" class="form-control" name="merchant_product_config_id[0]" value="{{isset($detail)?$detail->merchant_product_config_id:''}}">
                </div>
              </div>
              @if(!empty(isset($detail) && $detail->zalando_sizes))
                  @foreach($detail->zalando_sizes as $key => $v)
                      <div class="expended_html">
                          <div class="row">
                              <div class="col-md-2 form-group">
                                  <label>Size Ean</label>
                                  <input type="text" class="form-control" name="size_ean[0][]" value="{{$v['ean']}}" required>
                              </div>
                              <div class="col-md-2 form-group">
                                  <label>Size Sku</label>
                                  <input type="text" class="form-control" name="size_sku[0][]" value="{{$v['sku']}}" required>
                              </div>
                              <div class="col-md-1 form-group">
                                  <label>Size Title</label>
                                  <input type="text" class="form-control" name="size_title[0][]" value="{{$v['title']}}" required>
                              </div>
                              @foreach($countries as $code => $channel)
                                  <div class="col-md-1 form-group pr-0">
                                      <label>{{ $channel['country'] }} ({{ strtoupper($channel['currency']) }})</label>
                                      
                                      <input type="text" class="form-control" name="{{ $channel['country'] }}_price[0][]" 
                                            value="{{ !empty($v[$channel['country'].'_price']) ? $v[$channel['country'].'_price'] : '' }}" required>
                                      
                                      <label>Quantity</label>
                                      <input type="text" class="form-control" name="quantity_{{ $channel['country'] }}[0][]" 
                                            value="{{ !empty($v['quantity_'.$channel['country']]) ? $v['quantity_'.$channel['country']] : 0 }}" required>
                                  </div>
                              @endforeach

                              <div class="col-md-3 form-group">
                                  <label class="btn-block"><a href="javascript:;" class="removeNo btn-danger btn-sm btn-xs pull-right" style="margin-bottom:5px;"> &times;</a></label>
                                  <label><input type="checkbox" name="promotionPrice[0][]" value="1" onchange="$('#promotionl{{$countLoop}}').toggle()" {{ ($v['promotionPrice']??0) == 1?'checked':'' }}> Promo Price ?</label>
                              </div>
                          </div>
                          <div class="row" id="promotionl{{$countLoop}}" style="display: {{ $v['promotionPrice']??0 == 1?'flex':'none' }};">
                              <div class="col-md-2">
                                  <label>Start Date</label>
                                  <input type="date" name="start_date[0][]" class="form-control datepicker" value="{{!empty($v['start_date'])?date('Y-m-d',strtotime($v['start_date'])):''}}">
                              </div>
                              <div class="col-md-2">
                                  <label>End Date</label>
                                  <input type="date" name="end_date[0][]" class="form-control datepicker" value="{{!empty($v['end_date'])?date('Y-m-d',strtotime($v['end_date'])):''}}">
                              </div>
                              @foreach($countries as $channel)
                                  <div class="col-md-1 form-group pr-0">
                                      <label>{{ $channel['country'] }} ({{ strtoupper($channel['currency']) }})</label>
                                      
                                      <input type="text" class="form-control" 
                                            name="pro_{{ $channel['country'] }}_price[0][]" 
                                            value="{{ !empty($v['pro_'.$channel['country'].'_price']) ? $v['pro_'.$channel['country'].'_price'] : '' }}" required>
                                  </div>
                              @endforeach

                          </div>
                      </div>
                      @php 
                          $countLoop++;
                      @endphp
                  @endforeach
              @endif
              <div class="new_sizes"></div>
              <div class="text-center">
                  <a href="javascript:void(0);" class="btn-primary btn addmoresize" data-index="0">Add  Size</a>
              </div>
              <br>
              @if(isset($detail) && !empty($detail->zalando_images))
              @php $count = 1; @endphp
              @foreach($detail->zalando_images as $i)
              @php $path = $i['media_path']; @endphp
              <div class="row" id="deleteImage{{$count}}">
                  <div class="col-md-4 form-group">
                    <label>Image</label>
                    <input type="file" class="form-control" name="image[0][]"  >
                  </div>
                  <div class="col-md-4 form-group">
                    <label>Image Sort Key</label>
                    <input type="hidden" name="old_image[0][]" value="{{$i['media_path']}}">
                    <input type="text" class="form-control" name="image_sort[0][]" value="{{$i['media_sort_key']}}">
                  </div>
                  <div class="col-md-4 form-group">
                    <button type="button" class="btn btn-sm btn-danger delete-image" onclick="deleteImage('deleteImage{{$count}}','{{$path}}')"><i class="fa fa-trash"></i></button>
                    <!-- <a href="{{$i['media_path']}}" target="_blank"><img src="{{$i['media_path']}}" style="height: 200px;" class="img-thumbnail"></a> -->
                  </div>
              </div>
              @php $count++; @endphp
              @endforeach
              @else
              <div class="row">
                  <div class="col-md-4 form-group">
                    <label>Image</label>
                    <input type="file" class="form-control" name="image[0][]" >
                  </div>
                  <div class="col-md-4 form-group">
                    <label class="btn-block">Image Sort Key</label>
                    <input type="hidden" name="old_image[0][]" value="">
                    <input type="text" class="form-control" name="image_sort[0][]" required="" >
                  </div>
              </div>
              @endif
              <div class="new_images"></div>
              <p class="mb-0"><strong>Recommended Dimensions: </strong> Width : 1524, Height : 2200</p>
              <p ><strong>Required Dimensions: </strong> Min Width : 610, Min Height : 880, Max Width : 6000, Max Height : 9000 </p>
              <div class="text-center">
                  <a href="javascript:void(0);" class="btn-primary btn addmoreImage" data-index="0" >Add Image</a>
              </div>
              <div class="row">
                  <div class="col-md-4 form-group">
                    <label>Session </label>
                    <select name="session_code[0]" class="form-control" required="">
                        <option value="">Select</option>
                        @if(!empty($sessions))
                        @foreach($sessions->items as $s)
                        <option value="{{$s->label}}" {{isset($detail) && $detail->season_code == $s->label?'selected':''}}>{{$s->value->localized->en}}</option>
                        @endforeach
                        @endif
                    </select>
                  </div>
                  <div class="col-md-4 form-group">
                    <label>Supplier Color</label>
                    <input type="text" class="form-control" name="supplier_color[0]" required="" value="{{!empty($detail->supplier_color)?$detail->supplier_color:'white'}}">
                  </div>
              </div>

              <!-- Required Attributes Section -->
              <hr>
              <h5>Required Attributes (Outline-specific Material Fields)
                <span class="badge badge-info" id="loading_attrs_0" style="display:none;">Loading...</span>
              </h5>
              <div id="required_attributes_container_0" class="row">
                @if(isset($detail) && !empty($detail->required_attributes))
                  @foreach($detail->required_attributes as $attr_name => $attr_value)
                    @php
                      // Determine attribute type based on value structure
                      $is_material_array = is_array($attr_value) && isset($attr_value[0]) && is_array($attr_value[0]) && isset($attr_value[0]['material_code']);
                      $attr_type = $is_material_array ? 'material_array' : 'text';
                      $attr_display_name = str_replace(['material.', 'color_code.', '_'], ['', '', ' '], $attr_name);
                    @endphp

                    @if($is_material_array)
                      {{-- Material array type --}}
                      <div class="required-attr-section col-md-6" data-attr-name="{{$attr_name}}" data-attr-type="material_array">
                        <h6>{{$attr_display_name}}</h6>
                        <input type="hidden" name="required_attr_name[0][]" value="{{$attr_name}}">
                        <input type="hidden" name="required_attr_type[0][{{$attr_name}}]" value="material_array">
                        @foreach($attr_value as $attr_key => $attr_val)
                          <div class="expended_html">
                            <div class="row">
                              <div class="col-md-6 form-group">
                                <label>Material</label>
                                <select name="required_attr_value[0][{{$attr_name}}][{{$attr_key}}][material_code]" class="form-control">
                                  <option value="">Select</option>
                                  @if(!empty($materials))
                                    @foreach($materials->items as $mi)
                                      <option value="{{$mi->label}}" {{$attr_val["material_code"] == $mi->label?"selected":""}}>{{$mi->value->localized->en}}</option>
                                    @endforeach
                                  @endif
                                </select>
                              </div>
                              <div class="col-md-6 form-group">
                                <label class="btn-block">Material Percentage <a href="javascript:;" class="removeNo btn-danger btn-sm btn-xs pull-right" style="margin-bottom:5px;"> &times;</a></label>
                                <input type="number" step=".01" class="form-control material-percentage" name="required_attr_value[0][{{$attr_name}}][{{$attr_key}}][material_percentage]" placeholder="%" value="{{$attr_val["material_percentage"]}}" min="0" max="100">
                              </div>
                            </div>
                          </div>
                        @endforeach
                        <div class="new_required_attr_material"></div>
                        @php
                          $total_percentage = 0;
                          foreach($attr_value as $mat) {
                            $total_percentage += $mat['material_percentage'] ?? 0;
                          }
                          $alert_class = 'alert-info';
                          if ($total_percentage == 100) {
                            $alert_class = 'alert-success';
                          } elseif ($total_percentage > 95 && $total_percentage < 105) {
                            $alert_class = 'alert-warning';
                          } else {
                            $alert_class = 'alert-danger';
                          }
                        @endphp
                        <div class="percentage-total alert {{$alert_class}}">
                          <strong>Total: <span class="total-value">{{$total_percentage}}</span>%</strong> (Should be 100%)
                        </div>
                        <div class="text-center">
                          <a href="javascript:void(0);" class="btn-sm btn-info addmore_required_attr" data-index="0" data-attr-name="{{$attr_name}}">Add Material for {{$attr_display_name}}</a>
                        </div>
                        <br>
                      </div>
                    @elseif(strpos($attr_name, 'color_code.') === 0)
                      {{-- Color type --}}
                      <div class="required-attr-section col-md-6" data-attr-name="{{$attr_name}}" data-attr-type="color">
                        <h6>{{$attr_display_name}}</h6>
                        <input type="hidden" name="required_attr_name[0][]" value="{{$attr_name}}">
                        <input type="hidden" name="required_attr_type[0][{{$attr_name}}]" value="color">
                        <div class="form-group">
                          <label>Color Code</label>
                          <select name="required_attr_value[0][{{$attr_name}}]" class="form-control" required>
                            <option value="">Select Color</option>
                            @if(!empty($color_code))
                              @foreach($color_code->items as $cc)
                                <option value="{{$cc->label}}" {{$attr_value == $cc->label ? 'selected' : ''}}>{{$cc->value->localized->en}}</option>
                              @endforeach
                            @endif
                          </select>
                        </div>
                      </div>
                    @else
                      {{-- Text type --}}
                      <div class="required-attr-section col-md-6" data-attr-name="{{$attr_name}}" data-attr-type="text">
                        <h6>{{$attr_display_name}}</h6>
                        <input type="hidden" name="required_attr_name[0][]" value="{{$attr_name}}">
                        <input type="hidden" name="required_attr_type[0][{{$attr_name}}]" value="text">
                        <div class="form-group">
                          <label>Value</label>
                          <input type="text" name="required_attr_value[0][{{$attr_name}}]" class="form-control" value="{{$attr_value}}" required>
                        </div>
                      </div>
                    @endif
                  @endforeach
                @endif
              </div>

              <div class="form-group">
                  <label>Body</label>
                  <textarea name="body[0]" class="form-control editor">{{isset($detail)?$detail->body_html:''}}</textarea>
              </div>
            </div>
        </div>
      </div>
    </div>
    @foreach($variants as $ind => $v)
    @php 
      $ind++;
    @endphp
    <input type="hidden" name="id[{{$ind}}]" value="{{isset($v)?$v->id:''}}">
    <div class="card" id="clone">
      <div class="card-header" id="headingTwo{{ $v->id }}">
        <h5 class="mb-0">
          <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#collapse{{ $v->id }}" aria-expanded="false" aria-controls="collapse{{ $v->id }}">
            Article Variant 
          </button>
          <a href="javascript:;" class="remove-variant">Remove Variant</a>
        </h5>
      </div>
      <div id="collapse{{ $v->id }}" class="collapse" aria-labelledby="headingTwo{{ $v->id }}" data-parent="#accordion">
        <div class="card-body">
          <div class="row">
            <div class="col-md-12">
                <div class="row">
                  <div class="col-md-2 form-group">
                    <label>Primary Color Code</label>
                    <select name="color_code[{{$ind}}]" class="form-control">
                      <option value="">Select</option>
                      @if(!empty($color_code))
                      @foreach($color_code->items as $cc)
                      <option value="{{$cc->label}}" {{isset($v) && $v->color_code == $cc->label?'selected':''}}>{{$cc->value->localized->en}}</option>
                      @endforeach
                      @endif
                    </select>
                </div>
                <div class="col-md-2 form-group">
                    <label>Article Variant ID</label>
                    <input type="text" class="form-control" name="merchant_product_config_id[{{$ind}}]" value="{{isset($v)?$v->merchant_product_config_id:''}}">
                </div>
              </div>
              @if(!empty(isset($v) && $v->zalando_sizes))
              @foreach($v->zalando_sizes as $key => $vs)
              <div class="expended_html">
                  <div class="row">
                    <div class="col-md-2 form-group">
                        <label>Size Ean</label>
                        <input type="text" class="form-control" name="size_ean[{{$ind}}][]"  value="{{$vs['ean']}}" required="">
                    </div>
                    <div class="col-md-2 form-group">
                        <label>Size Sku</label>
                        <input type="text" class="form-control" name="size_sku[{{$ind}}][]"  value="{{$vs['sku']}}" required="">
                    </div>
                    <div class="col-md-1 form-group">
                        <label>Size Title</label>
                        <input type="text" class="form-control" name="size_title[{{$ind}}][]"  value="{{$vs['title']}}" required="">
                    </div>
                    @foreach($countries as $channel)
                        <div class="col-md-1 form-group pr-0">
                            <label>{{ $channel['country'] }} ({{ strtoupper($channel['currency']) }})</label>
                    
                            <input type="text" class="form-control" name="{{ $channel['country'] }}_price[{{ $ind }}][]" 
                                      value="{{ !empty($vs[$channel['country'].'_price']) ? $vs[$channel['country'].'_price'] : '' }}" required>
                            <label>Quantity</label>
                            <input type="text" class="form-control" name="quantity_{{ $channel['country'] }}[{{ $ind }}][]" 
                                  value="{{ !empty($vs['quantity_'.$channel['country']]) ? $vs['quantity_'.$channel['country']] : 0 }}" required>
                        </div>
                    @endforeach
                
                    <div class="col-md-3 form-group">
                        <label class="btn-block"><a href="javascript:;" class="removeNo btn-danger btn-sm btn-xs pull-right" style="margin-bottom:5px;"> &times;</a></label>
                        <label><input type="checkbox" name="promotionPrice[{{ $ind }}][]" value="1" onchange="$('#promotionl{{$countLoop}}').toggle()" {{ $vs['promotionPrice']??0 == 1?'checked':'' }}> Promo Price ?</label>
                    </div>
                </div>
                <div class="row" id="promotionl{{$countLoop}}" style="display: {{ $vs['promotionPrice']??0 == 1?'flex':'none' }};">
                    <div class="col-md-2">
                        <label>Start Date</label>
                        <input type="date" name="start_date[{{ $ind }}][]" class="form-control datepicker" value="{{!empty($vs['start_date'])?date('Y-m-d',strtotime($vs['start_date'])):''}}">
                    </div>
                    <div class="col-md-2">
                        <label>End Date</label>
                        <input type="date" name="end_date[{{ $ind }}][]" class="form-control datepicker" value="{{!empty($vs['end_date'])?date('Y-m-d',strtotime($vs['end_date'])):''}}">
                    </div>
                    @foreach($countries as $channel)
                      <div class="col-md-1 form-group pr-0">
                          <label>{{ $channel['country'] }} ({{ strtoupper($channel['currency']) }})</label>
                          <input type="text" class="form-control" name="pro_{{ $channel['country'] }}_price[{{ $ind }}][]" value="{{ !empty($vs['pro_'.$channel['country'].'_price']) ? $vs['pro_'.$channel['country'].'_price'] : '' }}">
                      </div>
                  @endforeach

                </div>
              </div>
              @php 
                  $countLoop++;
                @endphp
              @endforeach
              @endif
              <div class="new_sizes"></div>
              <div class="text-center">
                  <a href="javascript:void(0);" class="btn-primary btn addmoresize" data-index="{{$ind}}">Add  Size</a>
              </div>
              <br>
              @if(isset($v) && !empty($v->zalando_images))
              @php $count = 1; @endphp
              @foreach($v->zalando_images as $i)
              @php $path = $i['media_path']; @endphp
              <div class="row" id="deleteImage{{$count}}">
                  <div class="col-md-4 form-group">
                    <label>Image</label>
                    <input type="file" class="form-control" name="image[{{$ind}}][]"  >
                  </div>
                  <div class="col-md-4 form-group">
                    <label>Image Sort Key</label>
                    <input type="hidden" name="old_image[{{$ind}}][]" value="{{$i['media_path']}}">
                    <input type="text" class="form-control" name="image_sort[{{$ind}}][]" value="{{$i['media_sort_key']}}">
                  </div>
                  <div class="col-md-4 form-group">
                    <button type="button" class="btn btn-sm btn-danger delete-image" onclick="deleteImage('deleteImage{{$count}}','{{$path}}')"><i class="fa fa-trash"></i></button>
                    <!-- <a href="{{$i['media_path']}}" target="_blank"><img src="{{$i['media_path']}}" style="height: 200px;" class="img-thumbnail"></a> -->
                  </div>
              </div>
              @php $count++; @endphp
              @endforeach
              @else
              <div class="row">
                  <div class="col-md-4 form-group">
                    <label>Image</label>
                    <input type="file" class="form-control" name="image[{{$ind}}][]" >
                  </div>
                  <div class="col-md-4 form-group">
                    <label class="btn-block">Image Sort Key</label>
                    <input type="hidden" name="old_image[{{$ind}}][]" value="">
                    <input type="text" class="form-control" name="image_sort[{{$ind}}][]" required="" >
                  </div>
              </div>
              @endif
              <div class="new_images"></div>
              <p class="mb-0"><strong>Recommended Dimensions: </strong> Width : 1524, Height : 2200</p>
              <p ><strong>Required Dimensions: </strong> Min Width : 610, Min Height : 880, Max Width : 6000, Max Height : 9000 </p>
              <div class="text-center">
                  <a href="javascript:void(0);" class="btn-primary btn addmoreImage" data-index="{{$ind}}" >Add Image</a>
              </div>
              <div class="row">
                  <div class="col-md-4 form-group">
                    <label>Session </label>
                    <select name="session_code[{{$ind}}]" class="form-control" required="">
                        <option value="">Select</option>
                        @if(!empty($sessions))
                        @foreach($sessions->items as $s)
                        <option value="{{$s->label}}" {{isset($v) && $v->season_code == $s->label?'selected':''}}>{{$s->value->localized->en}}</option>
                        @endforeach
                        @endif
                    </select>
                  </div>
                  <div class="col-md-4 form-group">
                    <label>Supplier Color</label>
                    <input type="text" class="form-control" name="supplier_color[{{$ind}}]" required="" value="{{!empty($v->supplier_color)?$v->supplier_color:'white'}}">
                  </div>
              </div>

              <!-- Required Attributes Section for Variants -->
              <hr>
              <h5>Required Attributes (Outline-specific Fields)</h5>
              <div id="required_attributes_container_{{$ind}}">
                @if(isset($v) && !empty($v->required_attributes))
                  @foreach($v->required_attributes as $attr_name => $attr_values)
                    <div class="required-attr-section" data-attr-name="{{$attr_name}}">
                      <h6>{{str_replace('material.', '', $attr_name)}}</h6>
                      <input type="hidden" name="required_attr_name[{{$ind}}][]" value="{{$attr_name}}">
                      @foreach($attr_values as $attr_key => $attr_val)
                        <div class="expended_html">
                          <div class="row">
                            <div class="col-md-4 form-group">
                              <label>Material</label>
                              <select name="required_attr_value[{{$ind}}][{{$attr_name}}][{{$attr_key}}][material_code]" class="form-control">
                                <option value="">Select</option>
                                @if(!empty($materials))
                                  @foreach($materials->items as $mi)
                                    <option value="{{$mi->label}}" {{$attr_val["material_code"] == $mi->label?"selected":""}}>{{$mi->value->localized->en}}</option>
                                  @endforeach
                                @endif
                              </select>
                            </div>
                            <div class="col-md-4 form-group">
                              <label class="btn-block">Material Percentage <a href="javascript:;" class="removeNo btn-danger btn-sm btn-xs pull-right" style="margin-bottom:5px;"> &times;</a></label>
                              <input type="number" step=".01" class="form-control" name="required_attr_value[{{$ind}}][{{$attr_name}}][{{$attr_key}}][material_percentage]" placeholder="%" value="{{$attr_val["material_percentage"]}}">
                            </div>
                          </div>
                        </div>
                      @endforeach
                      <div class="new_required_attr_material"></div>
                      <div class="text-center">
                        <a href="javascript:void(0);" class="btn-sm btn-info addmore_required_attr" data-index="{{$ind}}" data-attr-name="{{$attr_name}}">Add Material for {{str_replace('material.', '', $attr_name)}}</a>
                      </div>
                      <br>
                    </div>
                  @endforeach
                @endif
              </div>
              <div class="text-center">
                <a href="javascript:void(0);" class="btn-success btn add_required_attr_section" data-index="{{$ind}}">Add Required Attribute</a>
              </div>

              <div class="form-group">
                  <label>Body</label>
                  <textarea name="body[{{$ind}}]" class="form-control editor">{{isset($v)?$v->body_html:''}}</textarea>
              </div>
            </div>
        </div>
      </div>
    </div>
    @endforeach
    <div id="append-variant"></div>
  </div>

  <div class="row" style="margin:20px;">
    <div class="col-md-4">
      <label for=""><input type="checkbox" name="push" id="push" value="1"> Push Product to Zalando</label>
    </div>
    <div class="col-md-4">
      <center><button class="btn btn-primary" type="submit" id="save-form">Save Product</button></center>
    </div>
    <div class="col-md-4">
      <a href="javascript:;" id="add-variation" class="float-right btn btn-sm btn-success">Add Variation</a>
    </div>
  </div>
  
  </form>
 
</div>
@endsection
@push('script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.8/js/select2.min.js" defer></script>
<script type="text/javascript">
   $( document ).ready(function() {
    $("#save-form").click(function(){
      $(this).closest("form").get(0).submit();
    })
     $('.myselect').select2();

     // Load required attributes on page load if outline is already selected
     var initialOutline = $('#outline').val();
     if (initialOutline && !$('#required_attributes_container_0 .required-attr-section').length) {
       loadRequiredAttributes(initialOutline, 0);
     }
   });
</script>
<!-- <script  type="text/javascript" src="https://cdn.ckeditor.com/4.14.1/standard/ckeditor.js"></script> -->
<script type="text/javascript">
   $( document ).ready(function() {

    $(document).on("click", ".remove-variant", function(){
      $(this).closest('.card').remove();
    })

    var index = {{ count($variants)+1 }};
      $("#add-variation").click(function(){
        var variant_html = getVariantTemplate(index);
        var variantTemplate = `<div class="card">
          <div class="card-header" id="headingOne`+index+`">
          <input type="hidden" name="id[`+index+`]" value="">
            <h5 class="mb-0">
              <button class="btn btn-link" data-toggle="collapse" data-target="#collapseOne`+index+`" aria-expanded="true" aria-controls="collapseOne`+index+`">
                Article Variation 
              </button>
              <a href="javascript:;" class="remove-variant">Remove Variant</a>
            </h5>
          </div>

          <div id="collapseOne`+index+`" class="collapse" aria-labelledby="headingOne`+index+`" data-parent="#accordion">
            <div class="card-body">
              `+variant_html+`
            </div>
          </div>
        </div>`
        index++;
        $("#append-variant").append(variantTemplate);
      })

      // CKEDITOR.replace( 'body' );
      var count = 1;
       $(document).on("click", ".addmoreImage", function() {
        var index = $(this).attr("data-index");
         var number_html =
         '<div class="expended_html"> <div class="row">\
                  <div class="col-md-4 form-group">\
                   <label>Image</label>\
                   <input type="file" class="form-control" name="image['+index+'][]" >\
                 </div>\
                 <div class="col-md-4 form-group">\
                   <label class="btn-block">Image Sort Key <a href="javascript:;" class="removeNo  btn-danger btn-sm btn-xs pull-right" style="margin-bottom:5px;" > &times;</a></label>\
                   <input type="hidden" name="old_image['+index+'][]" value="">\
                   <input type="text" class="form-control" name="image_sort['+index+'][]" required="" >\
                 </div>\
               </div>\
         </div>';
   
        //  $(".new_images").append(number_html);
         $(this).parent().prev().append(number_html);
   
         count++;
   
         $(".removeNo").click(function() {
           $(this).closest('.expended_html').remove();
           count--;
   
           $("#addmoreImage").show();
         });
       });
   
   
       var count = {{ isset($detail)?$countLoop:0 }};
       $(document).on("click", ".addmoresize", function() {
    var index = $(this).attr("data-index");
    var identefir = "$('#promotionl"+count+"')";
    var countries = @json($countries);
    var number_html = '<div class="expended_html">\
            <div class="row">\
                <div class="col-md-2 form-group">\
                    <label>Size Ean</label>\
                    <input type="text" class="form-control" name="size_ean['+index+'][]" required="">\
                </div>\
                <div class="col-md-2 form-group">\
                    <label>Size Sku</label>\
                    <input type="text" class="form-control" name="size_sku['+index+'][]" required="">\
                </div>\
                <div class="col-md-1 form-group">\
                    <label>Size Title</label>\
                    <input type="text" class="form-control" name="size_title['+index+'][]" required="">\
                </div>';
                
    @foreach ($countries as $country)
        var currency = "{{ $country['currency'] }}"; 
        var country = "{{ $country['country'] }}"; 
        number_html += '<div class="col-md-1 form-group pr-0">\
                    <label>'+country+' ('+currency+')</label>\
                    <input type="text" class="form-control" name="'+country+'_price['+index+'][]" required="">\
                    <label>Quantity</label>\
                    <input type="text" class="form-control" name="quantity_'+country+'['+index+'][]" required="">\
                </div>';
    @endforeach

    number_html += '<div class="col-md-3 form-group">\
                    <label class="btn-block"><a href="javascript:;" class="removeNo btn-danger btn-sm btn-xs pull-right" style="margin-bottom:5px;"> &times;</a></label>\
                    <label><input type="checkbox" name="promotionPrice['+index+'][]" onchange="'+identefir+'.toggle()"> Promo Price ?</label>\
                </div>\
            </div>\
            <div class="row" id="promotionl'+count+'" style="display: none;">\
                <div class="col-md-2">\
                    <label>Start Date</label>\
                    <input type="date" name="start_date['+index+'][]" class="form-control datepicker">\
                </div>\
                <div class="col-md-2">\
                    <label>End Date</label>\
                    <input type="date" name="end_date['+index+'][]" class="form-control datepicker">\
                </div>';

    @foreach ($countries as $country)
        var currency = "{{ $country['currency'] }}"; 
        var country = "{{ $country['country'] }}"; 
        number_html += '<div class="col-md-1 form-group pr-0">\
                    <label>'+country+' ('+currency+')</label>\
                    <input type="text" class="form-control" name="pro_'+country+'_price['+index+'][]">\
                </div>';
    @endforeach

    number_html += '</div></div>';


    $(this).parent().prev().append(number_html);
    count++;

    $(".removeNo").click(function() {
        $(this).closest('.expended_html').remove();
        count--;
        $("#addmoresize").show();
    });
});

   
      
   });
   function deleteImage(id,path) {
   var removethis = "{{url('public/uploads/')}}"
   var path =  path.replace(removethis, "");
   $.get("{{url('delete-image')}}"+path, function(data, status){
   $('#'+id).remove();
   });
   }
   
   setTimeout(function(){ $("#removemessage").remove(); }, 6000);

   function getVariantTemplate(index){
    var variant_html = `<div class="card-body">
          <div class="row">
            <div class="col-md-12">
                <div class="row">
                  <div class="col-md-2 form-group">
                    <label>Primary Color Code</label>
                    <select name="color_code[`+index+`]" class="form-control">
                      <option value="">Select</option>
                      @if(!empty($color_code))
                      @foreach($color_code->items as $cc)
                      <option value="{{$cc->label}}" >{{$cc->value->localized->en}}</option>
                      @endforeach
                      @endif
                    </select>
                </div>
                <div class="col-md-2 form-group">
                    <label>Article Variant ID</label>
                    <input type="text" class="form-control" name="merchant_product_config_id[`+index+`]" value="">
                </div>
              </div>
              <div class="new_sizes"></div>
              <div class="text-center">
                  <a href="javascript:void(0);" class="btn-primary btn addmoresize" data-index="`+index+`">Add  Size</a>
              </div>
              <br>
              <div class="row">
                  <div class="col-md-4 form-group">
                    <label>Image</label>
                    <input type="file" class="form-control" name="image[`+index+`][]" >
                  </div>
                  <div class="col-md-4 form-group">
                    <label class="btn-block">Image Sort Key</label>
                    <input type="hidden" name="old_image[`+index+`][]" value="">
                    <input type="text" class="form-control" name="image_sort[`+index+`][]" required="" >
                  </div>
              </div>
              <div class="new_images"></div>
              <p class="mb-0"><strong>Recommended Dimensions: </strong> Width : 1524, Height : 2200</p>
              <p ><strong>Required Dimensions: </strong> Min Width : 610, Min Height : 880, Max Width : 6000, Max Height : 9000 </p>
              <div class="text-center">
                  <a href="javascript:void(0);" class="btn-primary btn addmoreImage" data-index="`+index+`" >Add Image</a>
              </div>
              <div class="row">
                  <div class="col-md-4 form-group">
                    <label>Session </label>
                    <select name="session_code[`+index+`]" class="form-control" required="">
                        <option value="">Select</option>
                        @if(!empty($sessions))
                        @foreach($sessions->items as $s)
                        <option value="{{$s->label}}">{{$s->value->localized->en}}</option>
                        @endforeach
                        @endif
                    </select>
                  </div>
                  <div class="col-md-4 form-group">
                    <label>Supplier Color</label>
                    <input type="text" class="form-control" name="supplier_color[`+index+`]" required="" value="">
                  </div>
              </div>
              <hr>
              <h5>Required Attributes (Outline-specific Material Fields)</h5>
              <div id="required_attributes_container_`+index+`"></div>
              <div class="text-center">
                <a href="javascript:void(0);" class="btn-success btn add_required_attr_section" data-index="`+index+`">Add Required Attribute</a>
              </div>
              <div class="form-group">
                  <label>Body</label>
                  <textarea name="body[`+index+`]" class="form-control editor"></textarea>
              </div>
            </div>
        </div>`;
        return variant_html;
   }

   // Automatically load required attributes when outline changes
   $(document).on("change", "#outline", function() {
     var outlineValue = $(this).val();
     if (outlineValue) {
       loadRequiredAttributes(outlineValue, 0);
     }
   });

   // Function to load required attributes from API
   function loadRequiredAttributes(outline, index) {
     $('#loading_attrs_' + index).show();

     $.ajax({
       url: "{{ url('/get-outline-attributes') }}/" + outline,
       type: 'GET',
       success: function(response) {
        
         $('#loading_attrs_' + index).hide();

         if (response.success) {
           if (response.attributes && response.attributes.length > 0) {
             // Clear existing attributes
             $('#required_attributes_container_' + index).empty();

             // Add each required attribute
             response.attributes.forEach(function(attr) {
               var attr_display_name = attr.label || attr.name.replace('material.', '').replace(/_/g, ' ');

               var attr_html = '';

               // Generate different HTML based on attribute type
               if (attr.type === 'material_array') {
                 // Material array type - show material dropdowns
                 attr_html = '<div class="required-attr-section col-md-6" data-attr-name="' + attr.name + '" data-attr-type="material_array">\
                   <h6>' + attr_display_name + (attr.description ? ' <small class="text-muted">(' + attr.description + ')</small>' : '') + '</h6>\
                   <input type="hidden" name="required_attr_name[' + index + '][]" value="' + attr.name + '">\
                   <input type="hidden" name="required_attr_type[' + index + '][' + attr.name + ']" value="material_array">\
                   <div class="new_required_attr_material"></div>\
                   <div class="text-center">\
                     <a href="javascript:void(0);" class="btn-sm btn-info addmore_required_attr" data-index="' + index + '" data-attr-name="' + attr.name + '">Add Material for ' + attr_display_name + '</a>\
                   </div>\
                 </div>';
               } else if (attr.type === 'color') {
                 // Color type - show color dropdown
                 attr_html = '<div class="required-attr-section col-md-6" data-attr-name="' + attr.name + '" data-attr-type="color">\
                   <h6>' + attr_display_name + (attr.description ? ' <small class="text-muted">(' + attr.description + ')</small>' : '') + '</h6>\
                   <input type="hidden" name="required_attr_name[' + index + '][]" value="' + attr.name + '">\
                   <input type="hidden" name="required_attr_type[' + index + '][' + attr.name + ']" value="color">\
                   <div class="form-group">\
                     <label>Color Code</label>\
                     <select name="required_attr_value[' + index + '][' + attr.name + ']" class="form-control" required>\
                       <option value="">Select Color</option>\
                       @if(!empty($color_code))\
                       @foreach($color_code->items as $cc)\
                       <option value="{{$cc->label}}">{{$cc->value->localized->en}}</option>\
                       @endforeach\
                       @endif\
                     </select>\
                   </div>\
                 </div>';
               } else {
                 // Text type - show simple text input
                 attr_html = '<div class="required-attr-section col-md-6" data-attr-name="' + attr.name + '" data-attr-type="text">\
                   <h6>' + attr_display_name + (attr.description ? ' <small class="text-muted">(' + attr.description + ')</small>' : '') + '</h6>\
                   <input type="hidden" name="required_attr_name[' + index + '][]" value="' + attr.name + '">\
                   <input type="hidden" name="required_attr_type[' + index + '][' + attr.name + ']" value="text">\
                   <div class="form-group">\
                     <label>Value</label>\
                     <input type="text" name="required_attr_value[' + index + '][' + attr.name + ']" class="form-control" required>\
                   </div>\
                 </div>';
               }

               $('#required_attributes_container_' + index).append(attr_html);
             });
           } else {
            
             $('#required_attributes_container_' + index).html('<p class="text-muted">No required material attributes for this outline.</p>');
           }
         } else {
           console.error('API returned error:', response.message);
           $('#required_attributes_container_' + index).html('<p class="text-danger">Error: ' + response.message + '</p>');
         }
       },
       error: function(xhr, status, error) {
         $('#loading_attrs_' + index).hide();
         console.error('AJAX Error:', {status: status, error: error, response: xhr.responseText});

         try {
           var errorResponse = JSON.parse(xhr.responseText);
           console.error('Error details:', errorResponse);
           $('#required_attributes_container_' + index).html('<p class="text-danger">Error: ' + (errorResponse.message || 'Failed to load attributes') + '</p>');
         } catch(e) {
           $('#required_attributes_container_' + index).html('<p class="text-danger">Error loading required attributes. Check console for details.</p>');
         }
       }
     });
   }

   // Handle adding material to required attribute
   $(document).on("click", ".addmore_required_attr", function() {
     var index = $(this).attr("data-index");
     var attr_name = $(this).attr("data-attr-name");
     var attr_section = $(this).closest('.required-attr-section');
     var material_count = attr_section.find('.expended_html').length;

     var material_html = '<div class="expended_html">\
       <div class="row">\
         <div class="col-md-4 form-group">\
           <label>Material</label>\
           <select name="required_attr_value[' + index + '][' + attr_name + '][' + material_count + '][material_code]" class="form-control material-select">\
             <option value="">Select</option>\
             @if(!empty($materials))\
             @foreach($materials->items as $mi)\
             <option value="{{$mi->label}}">{{$mi->value->localized->en}}</option>\
             @endforeach\
             @endif\
           </select>\
         </div>\
         <div class="col-md-4 form-group">\
           <label class="btn-block">Material Percentage <a href="javascript:;" class="removeNo btn-danger btn-sm btn-xs pull-right" style="margin-bottom:5px;"> &times;</a></label>\
           <input type="number" step=".01" class="form-control material-percentage" name="required_attr_value[' + index + '][' + attr_name + '][' + material_count + '][material_percentage]" placeholder="%" min="0" max="100">\
         </div>\
       </div>\
     </div>';

     attr_section.find('.new_required_attr_material').append(material_html);
      initMaterialSelect2();
     // Add percentage total indicator if not exists
     if (!attr_section.find('.percentage-total').length) {
       attr_section.find('.addmore_required_attr').before('<div class="percentage-total alert alert-info"><strong>Total: <span class="total-value">0</span>%</strong> (Should be 100%)</div>');
     } else {
       // Show it if it was hidden
       attr_section.find('.percentage-total').show();
     }
   });

   // Calculate and update percentage total when values change
   $(document).on('input change', '.material-percentage', function() {
     var attr_section = $(this).closest('.required-attr-section');
     var total = 0;

     attr_section.find('.material-percentage').each(function() {
       var val = parseFloat($(this).val()) || 0;
       total += val;
     });

     var totalDisplay = attr_section.find('.total-value');
     totalDisplay.text(total.toFixed(2));

     // Change color based on total
     var totalAlert = attr_section.find('.percentage-total');
     if (total === 100) {
       totalAlert.removeClass('alert-info alert-warning alert-danger').addClass('alert-success');
     } else if (total > 95 && total < 105) {
       totalAlert.removeClass('alert-info alert-success alert-danger').addClass('alert-warning');
     } else {
       totalAlert.removeClass('alert-info alert-success alert-warning').addClass('alert-danger');
     }
   });

   // Remove material and recalculate total
   $(document).on('click', '.removeNo', function() {
     var attr_section = $(this).closest('.required-attr-section');
     $(this).closest('.expended_html').remove();

     // Check if any materials left
     var material_count = attr_section.find('.material-percentage').length;

     if (material_count === 0) {
       // Hide percentage total if no materials left
       attr_section.find('.percentage-total').hide();
     } else {
       // Recalculate total
       setTimeout(function() {
         attr_section.find('.material-percentage').first().trigger('change');
       }, 100);
     }
   });

   // Form validation on submit
   $('form').on('submit', function(e) {
     var hasError = false;
     var errorMessage = '';

     $('.required-attr-section[data-attr-type="material_array"]').each(function() {
       var attr_section = $(this);
       var attr_name = attr_section.attr('data-attr-name');
       var material_count = attr_section.find('.material-percentage').length;

       if (material_count > 0) {
         var total = 0;
         attr_section.find('.material-percentage').each(function() {
           var val = parseFloat($(this).val()) || 0;
           total += val;
         });

         if (Math.abs(total - 100) > 0.01) {
           hasError = true;
           var displayName = attr_section.find('h6').text();
           errorMessage += '• ' + displayName + ': Total is ' + total.toFixed(2) + '% (should be 100%)\n';
         }
       }
     });

     if (hasError) {
       e.preventDefault();
       alert('Material Percentage Error:\n\n' + errorMessage);
       return false;
     }
   });

   // Initialize Select2 on material dropdowns - searchable dropdown
   function initMaterialSelect2() {
     $('.material-select:not(.select2-hidden-accessible)').select2({
       placeholder: 'Search and select material',
       allowClear: true,
       width: '100%'
     });
   }

   // Call on page load
   $(document).ready(function() {
     initMaterialSelect2();
   });

</script>
@endpush
