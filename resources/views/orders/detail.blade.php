@extends('layouts.app')

@section('content')
<script src="https://kit.fontawesome.com/a076d05399.js"></script>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            @if(request()->has('message'))
            <h4 id="removemessage" style="padding: 10px;background:green;color: #fff">{{request()->get('message')}}</h4>
            @endif
            @if(request()->has('errorMessage'))
            <h4 id="removemessage" style="padding: 10px;background:red;color: #fff">{{request()->get('errorMessage')}}</h4>
            @endif
            
            @if(session()->has('success-msg'))
            <h4 id="removemessage" style="padding: 10px;background:green;color: #fff">{{session()->get('success-msg')}}</h4>
            @endif
            @if(session()->has('error-msg'))
            <h4 id="removemessage" style="padding: 10px;background:red;color: #fff">{{session()->get('error-msg')}}</h4>
            @endif
            
            <h3>{{ $detail->attributes->order_number }} - Detail <span class="pull-right">Status : {{ $detail->attributes->status }} <button class="btn btn-primary" onclick="$('#toggleBtn').toggle()"> Add Tracking</button></span></h3>
            <hr>
            
            <form action="{{ url('add-tracking/'.$detail->id) }}" method="post" id="toggleBtn" style="display:none;">
                @csrf
              <div class="form-group">
                <label for="">Tracking ID</label>
                <input type="text" name="tracking_id" class="form-control" required value="{{ $detail->attributes->tracking_number }}">
              </div>
              <div class="form-group">
                <label for="">Return Tracking ID</label>
                <input type="text" name="return_tracking_id" class="form-control" required value="{{ $detail->attributes->return_tracking_number }}">
              </div>
              <div class="form-group">
                <input type="submit" value="Update Tracking Details" class="btn btn-success">
              </div>
            </form>
           <div class="row">
             <div class="col-md-3">
               <strong>Order Date:</strong> {{ date('d M Y H:i',strtotime($detail->attributes->order_date)) }}
             </div>
             <div class="col-md-3">
               <strong>Delivey Date:</strong> {{ date('d M Y H:i',strtotime($detail->attributes->order_date)) }}
             </div>
             <div class="col-md-3">
               <strong> Name:</strong> {{ $detail->attributes->shipping_address->first_name }} {{ $detail->attributes->shipping_address->last_name }}
             </div>
              <div class="col-md-3">
               <strong> Email:</strong> {{ $detail->attributes->customer_email }}
             </div>
           </div>
           <br>
           <h5>Shipping Address</h5>
               <hr>
           <div class="row">
             <div class="col-md-3">
                <strong>Shipment Number:</strong> {{ $detail->attributes->shipment_number }}
             </div>
             <div class="col-md-3">
                <strong>Address:</strong> {{ $detail->attributes->shipping_address->address_line_1 }}
             </div>
              <div class="col-md-3">
                <strong>Zip Code:</strong> {{ $detail->attributes->shipping_address->zip_code }}
             </div>
               <div class="col-md-3">
                <strong>City:</strong> {{ $detail->attributes->shipping_address->city }}
             </div>
              <div class="col-md-3">
                <strong>Country:</strong> {{ $detail->attributes->shipping_address->country_code }}
             </div>
           </div>
           <br>
            <h5>Billing Address</h5>
               <hr>
           <div class="row">
         
             <div class="col-md-3">
                <strong>Address:</strong> {{ $detail->attributes->billing_address->address_line_1 }}
             </div>
              <div class="col-md-3">
                <strong>Zip Code:</strong> {{ $detail->attributes->billing_address->zip_code }}
             </div>
               <div class="col-md-3">
                <strong>City:</strong> {{ $detail->attributes->billing_address->city }}
             </div>
              <div class="col-md-3">
                <strong>Country:</strong> {{ $detail->attributes->billing_address->country_code }}
             </div>
           </div>
       
            <br>
            <h5>Items</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                   <thead>
                       <tr>
                        <th >Description</th>
                        <th >Size</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Actions</th>
                       </tr>
                   </thead>

                   <tbody>
                     
                    @foreach($included as $oi)
                @if($oi->type == 'OrderItem')
                    @php
                      $sizeTitle = $ean = $sku = "";
                      foreach($sizes as $size):
                        foreach($size as $sd):
                          
                          if($oi->attributes->external_id == $sd['sku']):
                            $sizeTitle = $sd['title'];
                            $ean = $sd['ean'];
                            $sku = $sd['sku'];
                          endif;
                        endforeach;
                      endforeach;
                    @endphp
                    <tr>
                        <td>{{ $oi->attributes->description}} - {{ $ean.' - '.$sku }}</td>
                        <td>
                          {{ $sizeTitle }}
                        </td>
                        <td> 
                          Quantity Initial: {{ $oi->attributes->quantity_initial }} <br>
                          Quantity Reserved: {{ $oi->attributes->quantity_reserved }} <br>
                          Quantity Shipped: {{ $oi->attributes->quantity_shipped }} <br>
                          Quantity Returned: {{ $oi->attributes->quantity_returned }} <br>
                          Quantity Canceled: {{ $oi->attributes->quantity_canceled }} <br></td>
                          <td>
                          @foreach($included as $line)
                            @if($line->type == 'OrderLine' && $line->attributes->order_item_id == $oi->id)
                           
                              {{ $line->attributes->price->amount }} {{ $line->attributes->price->currency }}
                              <hr>
                            @endif
                            
                          @endforeach
                          </td>
                          <td>
                            @foreach($included as $line)
                              
                              @if($line->type == 'OrderLine' && $line->id && $oi->id == $line->attributes->order_item_id)
                              
                              <div class="btn-group mb-2">
                                @if($line->attributes->status == "shipped")
                                <a href="{{ url('line-item-status/'.$detail->id.'/'.$oi->id.'/'.$line->id.'/returned') }}" class="btn btn-danger confirm">Change to Returned</a>
                                @endif
                                
                                @if($line->attributes->status == "initial")
                                  <a href="{{ url('line-item-status/'.$detail->id.'/'.$oi->id.'/'.$line->id.'/shipped') }}" class="btn btn-success confirm">Change to Shipped</a>
                                @endif
                                @if($line->attributes->status == "initial")
                                <a href="{{ url('line-item-status/'.$detail->id.'/'.$oi->id.'/'.$line->id.'/cancelled') }}" class="btn btn-info confirm">Change to Cancelled</a>
                                @endif
                                </div>
                                <hr>
                              @endif
                              
                              
                            @endforeach
                          </td>
                   </tr>
                   @endif
                   @endforeach
                   <tr>
                     <td></td>
                     <td></td>
                     <td>Total</td>
                     <td>{{ $detail->attributes->order_lines_price_amount }} {{ $detail->attributes->order_lines_price_currency }}</td>
                     <td></td>
                   </tr>
               </tbody>
           </table>
       </div>

        </div>
    </div>
</div>
@endsection
@push('script')
<script type="text/javascript">
    setTimeout(function(){ document.getElementById("removemessage").remove(); }, 6000);
    $(function(){
      $('a.confirm').click(function(){return confirm("are you sure?");});
    })
</script>

@endpush