@extends('layouts.app')

@section('content')
<div class="container">
    @if(request()->has('message'))
    <h4 id="removemessage" style="padding: 10px;background:green;color: #fff">{{request()->get('message')}}</h4>
    @endif
    @if(request()->has('errorMessage'))
    <h4 id="removemessage" style="padding: 10px;background:red;color: #fff">{{request()->get('errorMessage')}}</h4>
    @endif
    
    <div class="row justify-content-center">
        <div class="col-md-12">
            <form action="" class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="">Order Number</label>
                        <input type="text" name="order_number" class="form-control" value="{{ app('request')->has('order_number')?app('request')->get('order_number'):'' }}">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="">Order Status</label>
                        <select name="order_status" id="" class="form-control">
                            <option value="">All</option>
                            <option value="Initial" {{ app('request')->has('order_status') && app('request')->get('order_status') == 'Initial'?'selected':'' }}>Initial</option>
                            <option value="Approved" {{ app('request')->has('order_status') && app('request')->get('order_status') == 'Approved'?'selected':'' }}>Approved</option>
                            <option value="Fulfilled" {{ app('request')->has('order_status') && app('request')->get('order_status') == 'Fulfilled'?'selected':'' }}>Fulfilled</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="">Sale Channel</label>
                        <select name="sales_channel_id" id="sales_channel_id" class="form-control">
                            <option value="">All Channels</option>
                            @foreach(config('channels') as $name => $id)
                            <option value="{{ $id['id'] }}" {{ app('request')->has('sales_channel_id') && app('request')->get('sales_channel_id') == $id['id']?'selected':'' }}>{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="">Created After</label>
                        <input type="date" name="created_after" class="form-control" value="{{ app('request')->has('created_after')?app('request')->get('created_after'):'' }}">
                    </div>
                </div>
                <div class="col-md-12">
                    <input type="submit" class="pull-right btn btn-primary" value="Filter Orders">
                </div>
            </form>
        </div>

        <div class="col-md-12">
            
            <h3>Orders 
                <!-- <a href="{{url('orders?scrap_new=1')}}" class="pull-right btn btn-primary">Refresh</a> -->
            </h3>
            
            <br>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                   <thead>
                       <tr>
                        <th width="10%">Number</th>
                           <th>Date</th>
                           <th>Delivery Date</th>
                           <th>Customer Name</th>
                           <!--<th>Customer Email</th>-->
                           <th>Lines</th>
                           <th>Price</th>
                           <th>Locale</th>
                           <th>Status</th>
                           <th width="20%">Invoice</th>
                       </tr>
                   </thead>

                   <tbody>
                    @foreach($orders as $p)
                    @php
                        $locale = $p->attributes->locale == "fr-BE"?"fr-be":strtolower(explode("-", $p->attributes->locale)[1]);
                    @endphp
                    <tr>
                       <td> <a  href="{{url('order-detail/'.$p->id)}}">{{ $p->attributes->order_number }}</a></td>
                       <td>{{ date('d M Y H:i',strtotime($p->attributes->order_date)) }}</td>
                       <td>{{ date('d M Y H:i',strtotime($p->attributes->delivery_end_date)) }}</td>
                        <td>{{ $p->attributes->shipping_address->first_name }} {{ $p->attributes->shipping_address->last_name }}</td>
                        <!--<td>{{ $p->attributes->customer_email }}</td>-->
                        <td>{{ $p->attributes->order_lines_count }}</td>
                        <td>{{ $p->attributes->order_lines_price_amount }} {{ $p->attributes->order_lines_price_currency }}</td>
                        <td>{{ $p->attributes->locale }}</td>
                         <td>{{ $p->attributes->status }}</td>
                       <td>
                            <a   class="btn btn-info btn-sm" target="_blank" title="Invoice" href="{{url('print-invoice/'.$locale.'/'.$p->id)}}">IN</a>
                            
                            <a   class="btn btn-success btn-sm" target="_blank" href="{{url('print-delivery-note/'.$locale.'/'.$p->id)}}" title="Delivery Note">DN</a>
                            <a   class="btn btn-warning btn-sm" target="_blank" href="{{url('print-return-slip/'.$locale.'/'.$p->id)}}" title="Return Slip">RS</a>
                            <a   class="btn btn-danger btn-sm" target="_blank" href="{{url('print-return-flyer/'.$locale)}}" title="Download Flayer">FL</a>
                       </td>
                   </tr>
                   @endforeach
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
</script>

@endpush