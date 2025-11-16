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
                <h2 style="margin: 0;"><strong>Retourenschein</strong></h2>
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
                        <th align="left">Bestell-Nr.:</th>
                        <td>{{ $detail->attributes->order_number }}</td>
                    </tr>
                    <tr>
                        <th align="left">Rechnung vom:</th>
                        <td>{{ date('d.m.Y', strtotime($detail->attributes->order_date)) }}</td>
                    </tr>
                    <tr>
                        <th align="left">Kunder-Nr:</th>
                        <td>{{ $detail->attributes->customer_number }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <table>
        <tr>
            <td><p>Um einen Artikel zurückzusenden, folge bitte den Anweisungen für die Rückgabe. Wenn du einen  Artikel umtauschen möchtest, bestelle bitte anschließend deinen neuen Wunschartikel auf zalando.de.</p></td>
        </tr>
    </table>
    <table width="100%" cellpadding="5" border="1" cellspacing="0">
        
        <tr align="left">
            <th width="15%">Zalando Art. Nr.</th>
            <th width="15%">Händler Art. Nr.</th>
            <th width="30%">Artikelbezeichnung</th>
            <th width="10%">Größe</th>
            <th width="10%" bgcolor="#d8d8d8">Grund</th>
            <!-- <th width="20%">Stückpreis</th> -->
            <!-- <th width="15%">Gesamtbetrag</th> -->
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td bgcolor="#d8d8d8">&nbsp;</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td bgcolor="#d8d8d8">&nbsp;</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td bgcolor="#d8d8d8">&nbsp;</td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td bgcolor="#d8d8d8">&nbsp;</td>
        </tr>
    </table>
    <br>
    
    <table width="100%" bgcolor="#d8d8d8">
        <tr>
            <td>
                <p style="margin:0px;"><strong>Retourengründe:</strong></p>
                <p>
                1 Artikel gefällt nicht &nbsp; &nbsp; &nbsp; 3 zu klein &nbsp; &nbsp; &nbsp; 5 Lieferung zu spät &nbsp; &nbsp; &nbsp; 9 falscher Artikel <br>
                2 zu groß &nbsp; &nbsp; &nbsp; 4 Preis-Leistungs-Verhältnis &nbsp; &nbsp; &nbsp; 6 anders als dargestellt &nbsp; &nbsp; &nbsp; 10 defekter Artikel
                </p>
                <table  width="100%">
                    <tr>
                        <td width="80%">
                            <p style="font-size:14px;"><strong>*Falls der Artikel defekt: Wo befindet sich der Defekt? (erforderlich) </strong> </p>
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
                @if(explode("-", $detail->attributes->locale)[1] == "DE)
                <p style="margin:0px;"><strong>Kurzanleitung Retourensendung:</strong></p>
                <p>
                Retourengrund (Nummer) eintragen <br>
                Artikel inkl. Retourenschein verpacken <br>
                Retourenetikett aufkleben und Paket zur nächsten Filiale der Deutschen Post, DHL-Filiale oder  <br>
                DHL Packstation bringen <br>
                Für Umtausch gewünschte Artikel auf zalando.de bestellen <br>
                </p>
                @endif
                <p><strong>Hinweis für den Retourenversand:</strong> Es handelt sich bei dieser Sendung um einen Artikel eines Zalando-Partners. Bitte 
                retourniere die Ware direkt an den Partner und nicht an Zalando. Ausführliche Informationen zur Rückgabe findest du 
                auf der beiliegenden Anleitung</p>
                <p><strong>Hinweis für den Retourenversand von Kosmetikartikeln:</strong> Bitte verpacke die Produkte in der vollständigen 
Originalverpackung. <strong>Produkte mit einem Siegel verlieren das Rückgaberecht, falls dieses entfernt oder beschädigt 
wurde</strong>. Die gesetzlichen Gewährleistungs- und Widerrufsrechte bleiben hiervon jedoch unberührt.</p>
            </td>
        </tr>
    </table>
    
</body>
</html>