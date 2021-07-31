<?php
require 'nasbifunciones.php';
require '../../Shippo.php';
class ShippoServices extends Conexion
{
    public function verKey()
    {
        // $data = (array) json_decode(Shippo_Transaction::retrieve('75aede27264444b4a83ba1e8910203cb'), true);
        // $data = (array) json_decode(Shippo_Shipment::get_shipping_rates( array( 'id'=> '758b644cee829aaa3ce77ce7f9de6661' ) ), true);
        $data = (array) json_decode(Shippo_CustomsDeclaration::retrieve('ce40f47ae0904fbdbc3f30b4a75909a7'), true);
        $data2 = (array) json_decode(Shippo_CustomsItem::retrieve($data['items'][0]), true);


        // $fromAddress = json_decode(Shippo_Address::retrieve('0446fe2fa53d48d781f8878fc84662de')); //EEUU
        // $toAddress = json_decode(Shippo_Address::retrieve('bafb35e845aa4725a20c233e66e82a5f')); //COLOMBIA
        // $parcel = json_decode(Shippo_Parcel::retrieve('39b4d755c3044795a6d426337e327d1c'));
        // $parcel = (array) $parcel;
        // unset($parcel['extra']);


        // $shipment = json_decode(Shippo_Shipment::create(
        //     array(
        //         "address_from" => $fromAddress,
        //         "address_to" => $toAddress,
        //         "parcels" => array($parcel),
        //         "async" => false
        //     )
        // ));


        // $data = Shippo_Address::retrieve('bafb35e845aa4725a20c233e66e82a5f');
        // $data = Shippo_Address::validate('bafb35e845aa4725a20c233e66e82a5f');
        return [
            $data,
            $data2
        ];
    }

}
?>