@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            @if(request()->has('message'))
            <h4 id="removemessage" style="padding: 10px;background:green;color: #fff">{{request()->get('message')}}</h4>
            @endif
            @if(request()->has('errorMessage'))
            <h4 id="removemessage" style="padding: 10px;background:red;color: #fff">{{request()->get('errorMessage')}}</h4>
            @endif
            <h3>Products <span class="badge badge-primary">{{ count($products) }}</span> <div class="pull-right"><a href="{{route('check-live')}}" class="btn btn-success" >Check For Live</a> <a href="{{route('check-status')}}" class="btn btn-danger" >Check For Errors</a> <a href="{{route('check-stock')}}" class="btn btn-primary" >Check Stock</a> <!-- <a href="{{route('fetch-products')}}" class="btn btn-primary" style="  background-color: #dc356f;border-color:  #dc356f">Fetch Products From Shopify</a>  --> <a href="{{route('push-products')}}" class="btn btn-warning">Push Products To Zalando</a> <a href="{{route('add-product')}}" class="btn btn-danger">Add Product</a></div></h3>
            <br>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                   <thead>
                       <tr>
                           <th width="3%">ID</th>
                           <th width="25%">Title</th>
                           <th width="15%">Body</th>
                           <th width="15%">Tags</th>
                           <th width="12%">Created At</th>
                           <th width="10%">Status</th>
                           <th width="20%">Action</th>
                       </tr>
                   </thead>

                   <tbody>
                    @foreach($products as $p)
                    <tr {!! !is_null($p->errors) && $p->errors->count() > 0?'style="background:red;color:white"':'' !!}>
                       <td>{{ $p->merchant_product_model_id }}</td>
                       <td>{{ $p->title }}</td>
                       <td>{!! str_limit($p->body_html, 20) !!}</td>
                       <td>{{ $p->tags }}</td>
                       <td>{{ date('d M Y',strtotime($p->created_at)) }}</td>
                       <td>
                        @if($p->imported == 1)
                        Awaiting Approval
                        @elseif($p->imported == 2)
                        Completed
                        @elseif($p->imported == 3)
                        Push Prices
                        @elseif($p->imported == 4)
                        Price Pushed
                        @else
                        Pending
                        @endif</td>
                       <td><!-- @if($p->imported != 1)<a href="{{url('resubmit-product/'.$p->id)}}" class="btn btn-primary btn-sm"><i class="fas fa-sync-alt"></i></a>@endif  -->
                        <a href="{{route('edit-product',$p->id)}}" class="btn  btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                        
                        <a href="{{route('push-prices',$p->id)}}" title="Push Prices" class="btn  btn-primary btn-sm"><i class="fas fa-money-check-alt"></i></a>
                        
                        <a href="{{route('delete-product',$p->id)}}" onclick="return confirm('Are you Sure ?')" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                          &nbsp;  <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#stock{{$p->id}}">Stock</button>
                         <div id="stock{{$p->id}}" class="modal fade" role="dialog" style="color: black">
                          <div class="modal-dialog" style="max-width: 600px;">

                            <!-- Modal content-->
                            <div class="modal-content">
                              <div class="modal-header">
                                <h4 class="modal-title">Stock</h4>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                              </div>
                              <div class="modal-body">
                                @php
                                if(!empty($p->zalando_sizes)){
                            $sizes = $p->zalando_sizes;
                                }else{
                                  $sizes = $p->variants;
                                }
                                @endphp
                                @foreach($sizes as $key => $size)
                                <strong>Ean : </strong>{{!empty($size['ean'])?$size['ean']:''}} &nbsp;&nbsp;  <strong>Sku : </strong>{{!empty($size['sku'])?$size['sku']:''}} &nbsp;&nbsp;<strong>Size : </strong>{{!empty($size['title'])?$size['title']:''}} &nbsp;&nbsp; <strong>Quantity :</strong> {{!empty($size['quantity'])?$size['quantity']:'N/A'}}
                                <hr>
                                @endforeach
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                              </div>
                            </div>

                          </div>
                        </div>
                        @if(!is_null($p->errors) && $p->errors->count() > 0)
                    
                        <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#myModal{{$p->id}}"><i class="fas fa-question-circle"></i></button>
                    

                        <!-- Modal -->
                        <div id="myModal{{$p->id}}" class="modal fade" role="dialog" style="color: black">
                          <div class="modal-dialog modal-lg">

                            <!-- Modal content-->
                            <div class="modal-content">
                              <div class="modal-header">
                                <h4 class="modal-title">Rejection Reasons</h4>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                              </div>
                              <div class="modal-body">
                                @foreach($p->errors as $key => $error)
                                <strong>Ean :</strong>{{$error->ean}} &nbsp;&nbsp; <strong>Code :</strong>{{$error->status_code}}<br>
                                <p>{{$error->message}}</p>
                                <p>{{$error->detail}}</p>
                                @endforeach
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                              </div>
                            </div>

                          </div>
                        </div>
                       
                        @endif
                       </td>
                   </tr>
                   @endforeach
               </tbody>
           </table>
       </div>
       {!! $products->links() !!}

        </div>
    </div>
</div>
@endsection
@push('script')
<script type="text/javascript">
    setTimeout(function(){ document.getElementById("removemessage").remove(); }, 6000);
</script>

@endpush