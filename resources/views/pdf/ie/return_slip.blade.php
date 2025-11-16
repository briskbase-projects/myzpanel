<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <style>
        body{
            font-family: DejaVu Sans, Arial, sans-serif;font-size: 12px;
        }
    </style>
</head>
<body>

    <table width="100%">
        <tr valign="top">
            <td>
                <img src="{{ env('CLIENT_LOGO') }}" alt="" width="100">
                <h2 style="margin: 0;"><strong>Return Note</strong></h2>
            </td>
            <td align="right">
                <img src="{{ url('images/zalando.png') }}" alt="" width="200">
            </td>
        </tr>
        <tr>
            <td width="50%">
                <p>{{ $detail->attributes->shipping_address->first_name }} {{ $detail->attributes->shipping_address->last_name }}<br>
                {{ $detail->attributes->shipping_address->address_line_1 }}<br>
                {{ $detail->attributes->shipping_address->zip_code }} {{ $detail->attributes->shipping_address->city }}</p>
            </td>
            <td align="right" width="50%" bgcolor="#d8d8d8">
                <table width="100%" style="border: 1px solid #ddd;" cellpadding="5">
                  
                    <tr>
                        <th align="left">Order no.:</th>
                        <td>{{ $detail->attributes->order_number }}</td>
                    </tr>
                    <tr>
                        <th align="left">Invoice date:</th>
                        <td>{{ date('d.m.Y', strtotime($detail->attributes->order_date)) }}</td>
                    </tr>
                    <tr>
                        <th align="left">Customer number:</th>
                        <td>{{ $detail->attributes->customer_number }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <table>
        <tr>
            <td><p>Want to return an item? Simply follow the return instructions in the parcel. If you wish to get the item in
another size or style, please place a new order at zalando.ie.</p></td>
        </tr>
    </table>
    <table width="100%" cellpadding="5" border="1" cellspacing="0">
        
        <tr align="left">
            <th width="15%">Zalando ref no.</th>
            <th width="15%">Partner ref no.</th>
            <th width="30%">Article</th>
            <th width="10%">Size</th>
            <th width="10%" bgcolor="#d8d8d8">Reason</th>
            <!-- <th width="20%">Stückpreis</th> -->
            <!-- <th width="15%">Gesamtbetrag</th> -->
        </tr>
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
                <td>{{ $oi->attributes->article_id }}</td>
                <td>{{$sku}}</td>
                <td>{{ $oi->attributes->description }}</td>
                <td>({{$sizeTitle}})</td>
                <td></td>
            </tr>
            @endif
        @endforeach
    </table>
    <br>
    
    <table width="100%" bgcolor="#d8d8d8">
        <tr>
            <td>
                <p style="margin:0px;"><strong>Reason(s) for Return::</strong></p>
                <p>
                1 It doesn’t suit me &nbsp; &nbsp; &nbsp; 3 Too small &nbsp; &nbsp; &nbsp; 5 Arrived too late &nbsp; &nbsp; &nbsp; 9 Incorrect article <br>
                2 Too big &nbsp; &nbsp; &nbsp; 4 Insufficient quality &nbsp; &nbsp; &nbsp; 6 Not as expected &nbsp; &nbsp; &nbsp; 10 Faulty*
                </p>
                <table  width="100%">
                    <tr>
                        <td width="80%">
                            <p style="font-size:14px;"><strong>* If this product is faulty, please let us know what and where the fault is:</strong> </p>
                        </td>
                        <td width="20%"><input type="text" width="100%" style="height: 20px";></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <br>
    <table width="100%" bgcolor="#d8d8d8">
        <tr>
            <td>
                <p style="margin:0px;"><strong>Return instructions:</strong></p>
                <p>
                1. Tell us the reason for your return by entering the number of the return reason in the table above. <br>
                2. Place the item(s), including this return note, in a box. <br>
                3. Attach the return label to the outside of the package. <br>
                4. Drop it off at An Post. <br>
                </p>
                <p><strong>Note for the return:</strong> This shipment was sent by one of Zalando Partners. Please return the article(s) directly to the Partner, and not to Zalando. You will find more detailed return information in the included return flyer.</p>
                <p><strong>Note for the return of cosmetic articles:</strong> Please return the article in its complete original packaging. Sealed articles are excluded from the return right, if the seal was removed or damaged. The warranty and revocation rights remain unaffected.</p>
            </td>
        </tr>
    </table>
    
</body>
</html>