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
                <h2 style="margin: 0;"><strong>Bon de retour</strong></h2>
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
                        <th align="left">Numéro de commande :</th>
                        <td>{{ $detail->attributes->order_number }}</td>
                    </tr>
                    <tr>
                        <th align="left">Date de commande::</th>
                        <td>{{ date('d.m.Y', strtotime($detail->attributes->order_date)) }}</td>
                    </tr>
                    <tr>
                        <th align="left">Numéro de client:</th>
                        <td>{{ $detail->attributes->customer_number }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <table>
        <tr>
            <td><p>Pour renvoyer un article, suivez les instructions de retour sur le mode d’emploi pour les retours. Si
vous souhaitez obtenir cet article dans une taille différente, passez une nouvelle commande sur
www.zalando.fr.</p></td>
        </tr>
    </table>
    <table width="100%" cellpadding="5" border="1" cellspacing="0">
        
        <tr align="left">
            <th width="15%">Réf. Zalando</th>
            <th width="15%">Réf. Partenaire</th>
            <th width="30%">Article</th>
            <th width="10%">Taille</th>
            <th width="10%" bgcolor="#d8d8d8">Raison</th>
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
                <p style="margin:0px;"><strong>Raison du retour:</strong></p>
                <p>
                1 Ne me plaît pas  &nbsp; &nbsp; &nbsp; 3 Trop petit &nbsp; &nbsp; &nbsp; 5 Colis livré trop tard 9 Erreur d'article &nbsp; &nbsp; &nbsp; 9 falscher Artikel <br>
                2 Trop grand  &nbsp; &nbsp; &nbsp; 4 Qualité décevante &nbsp; &nbsp; &nbsp; 6 Ne correspond pas à mes attentes &nbsp; &nbsp; &nbsp; 10 Défectueux
                </p>
                <table  width="100%">
                    <tr>
                        <td width="80%">
                            <p style="font-size:14px;"><strong>* Si l'article est défectueux, où se trouve le défaut ?, (indiquez précisément l'endroit du défaut)</strong> </p>
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
                <p style="margin:0px;"><strong>Notice rapide sur les retours:</strong></p>
                <p>
                Mentionnez la raison du renvoi avec les articles que vous voulez retourner. <br>
                Placez l'article et le bon de retour dans le colis et refermez-le soigneusement. <br>
                Collez ensuite l'étiquette de retour sur le colis par-dessus l'étiquette de livraison. <br>
                Déposez votre colis dans le bureau de poste de votre choix. <br>
                </p>
                <p><strong>Indication concernant les retours:</strong> vous allez renvoyer un article vendu par un Partenaire Zalando. Merci de bien vouloir le renvoyer directement au partenaire et non à Zalando. Pour plus d'informations sur les retours, reportez-vous au mode d'emploi pour les retours inclus dans votre colis.</p>
                <p><strong>Important pour les retours de cosmétiques:</strong> En cas de retour, veuillez vous assurer que les produits sont renvoyés dans leur emballage d'origine. Notez que les produits munis d'un opercule perdent leur éligibilité au retour si l'opercule a été enlevé ou endommagé. Cela n'affecte toutefois en rien votre garantie légale et votre droit de rétractation.</p>
            </td>
        </tr>
    </table>
    
</body>
</html>