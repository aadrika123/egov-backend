<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.12.9/dist/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <!-- <link rel="stylesheet" href="style.css" /> -->
    <title>Document</title>

    <style>
        #logo {
            height: 80px;
            width: 80px;
        }

        #perfectSpacing {
            line-height: 0.8em;
        }

        #container {
            padding: 25px;
            margin-top: 25px;
        }
        .upperCase{
            text-transform: uppercase;
        }
    </style>
</head>

<body class="bg-light">

    <!-- <h1>&emsp;</h1> -->
    <div class="container bg-light border border-dark" id="container">
        <div class="row">
            <div class="col-md-12 text-center">
                <img src="{{asset('image/logo/jharkhand_log.png')}}" alt="logo" id="logo">
            </div>
        </div>
        <div class="row">
            <div class="col-md-3 text-center">
            </div>
            <div class="col-md-6 text-center">
                <h3>{{$noticeData->ulb_name}} <br/>राजस्व शाखा <br/>नोटिस</h3>
            </div>
            <div class="col-md-3 text-center">
            </div>
        </div>

        <div class="row">
            <div class="col-md-8" id="perfectSpacing">
                <p>प्रेषित,</p>
                <p>व्यवसाय का नाम : <strong class="upperCase"> {{$noticeData->firm_name}} </strong> </p>
                <p>नाम : <strong class="upperCase"> {{$noticeData->owner_name}} </strong> </p>
                <p>पता : <strong class="upperCase"> {{$noticeData->address}} </strong> </p>
                <p>मो० न० : <strong> {{$noticeData->mobile_no}} </strong> </p>
            </div>
            <!-- <div class="col-md-2"></div> -->
            <div class="col-md-4">
                <p>पत्रांक : <strong>{{$noticeData->notice_no}}</strong> </p>
                <p>दिनांक : <strong>{{date('d-m-Y',strtotime($reminder_notice_date))}}</strong></p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <p>
                    बजरिये नोटिस आपको सूचित किया जाता है कि राँची नगर निगम क्षेत्र में किसी भी भवन का गैर
                    आवासीय उपयोग करने के लिए झारखण्ड नगरपालिका अधिनियम, 2011 की धारा 455 के तहत
                    म्यूनिसिपल अनुज्ञप्ति प्राप्त करना अनिवार्य है।
                </p>
                <p>
                    अधोहस्ताक्षरी के संज्ञान में यह लाया गया है कि आपके द्वारा उपर्युक्त भवन का गैर आवासीय उपयोग
                    बिना म्यूनिसिपल अनुज्ञप्ति प्राप्त किये जा रहा है, जो कि झारखण्ड नगरपालिका अधिनियम, 2011 की धारा
                    455 का उल्लंघन है। यदि आपके पास भवन के गैरआवासीय उपयोग हेतु म्यूनिसिपल अनुज्ञप्ति प्राप्त है तो
                    जन सुविधा केन्द्र या निगम समर्थित 
                    <span class="upperCase">{{$agency_name??""}}</span>
                     के प्रतिनिधि
                    को उपलब्ध करायें।
                    <strong>(कृपया नोटिस की छायाप्रति भी साथ में संलग्न करें) </strong>
                </p>
                <p>अतएव आपको निदेशित किया जाता है कि नोटिस प्रप्ती के तीन दिनों के अन्दर उपर्युक्त भवन के
                    लिए म्यूनिसिपल अनुज्ञप्ति प्राप्त कर लें अथवा भवन का गैर आवासीय उपयोग बंद कर लें तथा
                    अधोहस्ताक्षरी को सूचित करें , , अन्यथा झारखण्ड नगरपालिका अधिनियम की धारा 187 एवं 600 के तहत
                    कार्यवाई प्रारम्भ की जायेगी एवं झारखण्ड नगरपालिका व्यापार अनुज्ञप्ति नियमावली 2017 के नियम 19
                    तथा 20 के तहत कार्यवाई की जायेगी। </p>

                <p class="text-center">
                    <strong>नोट : इसे अति आवश्यक समझें।</strong>
                </p>
            </div>
        </div>
        <div class="row">
            <div class="col-md-8"></div>
            <div class="col-md-4 text-center">
                <img src="{{asset('image/signatur/sign.png')}}" alt="signatur" >
                <p> <strong> {{$noticeData->ulb_name}} राजस्व शाखा नोटिस</strong></p>
                <p>-सह</p>
                <p><strong>उप नगर आयुक्त,</strong></p>
                <p>{{$noticeData->ulb_name}}</p>
            </div>
        </div>
    </div>
</body>

</html>