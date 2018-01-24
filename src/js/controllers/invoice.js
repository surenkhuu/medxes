// tab controller
app.controller('invoiceController', function($scope, Data) {


$scope.invoice = {
date: new Date().toLocaleDateString()

};
   $scope.PrintElem = function (elem)
    {
        Popup(angular.element('#printdiv').html());
    }

    function Popup(data) 
    {
        var mywindow = window.open('', 'my div', 'height=842,width=595');
          mywindow.document.write('<html><head><title></title>');
    //    mywindow.document.write('<html><head><title>'+ $scope.lastname +'</title>');
     //   mywindow.document.write('<link rel="stylesheet" href="css/bootstrap.css" type="text/css" />');
     //   mywindow.document.write('<link rel="stylesheet" href="css/animate.css" type="text/css" />');
     //   mywindow.document.write('<link rel="stylesheet" href="css/font-awesome.min.css" type="text/css" />');
     //   mywindow.document.write('<link rel="stylesheet" href="css/simple-line-icons.css" type="text/css" />');
     //   mywindow.document.write('<link rel="stylesheet" href="css/font.css" type="text/css" />');

       // mywindow.document.write('<link rel="stylesheet" href="css/app.css" type="text/css" />');
        mywindow.document.write('<link rel="stylesheet" href="vendor/modules/barcodeGenerator/barcode.css" type="text/css" />');
      //  mywindow.document.write('<style> html { background: none; } .text-right {text-align : right;} .col-xs-12 { width: 100%; float: left;} .col-xs-6 { width: 50%; float: left;} </style>');
 
        mywindow.document.write('</head><body style="height: 842px; width: 595px; margin-left: auto;margin-right: auto; font-size: 8px;"> ');
      //  mywindow.document.write(data);
       mywindow.document.write('<div barcode-generator="rd" style="height:40px; align:right;"></div>');
        
        mywindow.document.write('</body></html>');
        alert(mywindow.document.documentElement.innerHTML);
        mywindow.document.close(); // necessary for IE >= 10
    //    mywindow.focus(); // necessary for IE >= 10

        mywindow.print();
    //    mywindow.close();

        return true;
    }

  $scope.checkUserInfo  = function(rd){
  Data.post('checkUserInfo', {
               rd: rd
                }).then(function (results) {
                    if (results.status == "success") {
                      $scope.invoice.hideinfo = false;
                      $scope.invoice.ubchtunid = results.ubchtunid;
                      $scope.invoice.rd = results.rd;
                      $scope.invoice.lastname = results.lastname;
					  $scope.invoice.firstname = results.firstname;
					    $scope.invoice.mobile = results.mobile;
                      $scope.invoice.info = 
                       results.lastname + " овогтой " + results.firstname + " регистрийн дугаар:"+ results.rd +" Утас:"+ results.mobile + " и-мэйл:"+ results.email;
                    }
                }); 
}
});

