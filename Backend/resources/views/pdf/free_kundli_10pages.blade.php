<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="utf-8">
    <title>सम्पूर्ण वैदिक जन्म कुण्डली - {{ $name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 14mm 10mm 14mm;
        }
        body {
            font-family: 'DejaVu Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10.5px;
            color: #1F2937;
            background-color: #FFFFFF;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .page {
            page-break-after: always;
            box-sizing: border-box;
            position: relative;
            min-height: 980px;
        }
        .page:last-child {
            page-break-after: avoid;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #D97706;
            padding-bottom: 6px;
            margin-bottom: 10px;
        }
        .shloka {
            font-size: 11px;
            color: #B45309;
            font-weight: bold;
            margin-bottom: 2px;
        }
        .brand-title {
            font-size: 20px;
            font-weight: 800;
            color: #92400E;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }
        .subtitle {
            font-size: 10px;
            color: #78350F;
            margin-top: 2px;
        }
        .page-header-strip {
            display: table;
            width: 100%;
            border-bottom: 1px solid #D97706;
            padding-bottom: 4px;
            margin-bottom: 8px;
        }
        .page-header-strip .left {
            display: table-cell;
            text-align: left;
            font-size: 9px;
            color: #92400E;
            font-weight: bold;
        }
        .page-header-strip .right {
            display: table-cell;
            text-align: right;
            font-size: 9px;
            color: #6B7280;
        }
        .section-title {
            background: #FEF3C7;
            border-left: 4px solid #D97706;
            padding: 4px 8px;
            font-size: 11.5px;
            font-weight: bold;
            color: #78350F;
            margin-top: 10px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        th, td {
            border: 1px solid #E5E7EB;
            padding: 5px 6px;
            font-size: 9.5px;
            text-align: left;
        }
        th {
            background-color: #FDE68A;
            color: #78350F;
            font-weight: bold;
        }
        tr:nth-child(even) td {
            background-color: #FFFBEB;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 4px;
            font-size: 8.5px;
            font-weight: bold;
        }
        .badge-green { background: #D1FAE5; color: #065F46; }
        .badge-red { background: #FEE2E2; color: #991B1B; }
        .badge-amber { background: #FEF3C7; color: #92400E; }
        .badge-blue { background: #DBEAFE; color: #1E40AF; }
        .remedy-box {
            background-color: #FFFBEB;
            border: 1px dashed #D97706;
            border-radius: 6px;
            padding: 7px 10px;
            margin-top: 6px;
            font-size: 9.5px;
        }
        .footer {
            position: absolute;
            bottom: 5px;
            left: 0;
            right: 0;
            text-align: center;
            border-top: 1px solid #E5E7EB;
            padding-top: 4px;
            font-size: 8.5px;
            color: #9CA3AF;
        }
        .cover-box {
            border: 3px double #D97706;
            padding: 25px 20px;
            background: #FFFDF7;
            text-align: center;
            margin-top: 15px;
            border-radius: 8px;
        }
        .sacred-icon {
            font-size: 32px;
            color: #D97706;
            margin-bottom: 6px;
        }
        .devotee-card {
            background: #FEF3C7;
            border: 1px solid #F59E0B;
            border-radius: 6px;
            padding: 10px;
            margin: 15px auto;
            max-width: 440px;
            text-align: left;
        }
        .card-row {
            margin: 4px 0;
            font-size: 10.5px;
        }
        .card-label {
            font-weight: bold;
            color: #78350F;
            display: inline-block;
            width: 130px;
        }
        .card-val {
            color: #1F2937;
            font-weight: 600;
        }
        .house-block {
            border: 1px solid #E5E7EB;
            background: #FFFBEB;
            padding: 6px 8px;
            margin-bottom: 6px;
            border-radius: 4px;
        }
        .house-title {
            font-weight: bold;
            color: #92400E;
            font-size: 10px;
            margin-bottom: 2px;
        }
        .house-text {
            font-size: 9px;
            color: #374151;
            line-height: 1.35;
        }
    </style>
</head>
<body>

    <!-- ================= PAGE 1: COVER & JATAKA VIVARANA ================= -->
    <div class="page">
        <div class="header">
            <div class="shloka">॥ ॐ श्री गणेशाय नमः • ॐ नमः शिवाय • ॐ नमो भगवते वासुदेवाय ॥</div>
            <div class="brand-title">OnlinePuja.live</div>
            <div class="subtitle">श्री काशी विश्वनाथ धाम, वाराणसी • सम्पूर्ण वैदिक जन्म पत्रिका (Drik Ganita)</div>
        </div>

        <div class="cover-box">
            <div class="sacred-icon">🕉️ 🔱 🪔</div>
            <h1 style="color: #92400E; font-size: 22px; margin: 4px 0;">सम्पूर्ण वैदिक जन्म कुण्डली</h1>
            <p style="color: #B45309; font-size: 11px; margin: 2px 0 12px 0;">चित्रपक्षीय लाहिड़ी अयनांश (Lahiri Ayanamsha: {{ $ayanamsha }}°) पर आधारित प्रामाणिक जन्म विवरण</p>

            <div class="devotee-card">
                <div class="card-row"><span class="card-label">जातक का नाम:</span> <span class="card-val" style="color: #B91C1C; font-size: 12px;">{{ $name }}</span></div>
                <div class="card-row"><span class="card-label">जन्म तिथि:</span> <span class="card-val">{{ date('d F Y', strtotime($dob)) }}</span></div>
                <div class="card-row"><span class="card-label">जन्म समय:</span> <span class="card-val">{{ date('h:i A', strtotime($tob)) }} (IST)</span></div>
                <div class="card-row"><span class="card-label">जन्म स्थान:</span> <span class="card-val">{{ $pob }}</span></div>
                <div class="card-row"><span class="card-label">अक्षांश / रेखांश:</span> <span class="card-val">{{ round($lat, 4) }}° N, {{ round($lon, 4) }}° E</span></div>
                <div class="card-row"><span class="card-label">जन्म लग्न:</span> <span class="card-val">{{ $lagna['sign'] }} ({{ $lagna['degree'] }}°)</span></div>
                <div class="card-row"><span class="card-label">चन्द्र राशि:</span> <span class="card-val">{{ $panchang['moonSign'] }}</span></div>
                <div class="card-row"><span class="card-label">जन्म नक्षत्र:</span> <span class="card-val">{{ $panchang['nakshatra'] }} (चरण {{ $panchang['nakshatraPada'] }})</span></div>
            </div>
        </div>

        <div class="section-title">जन्म कालीन पंचांग विवरण (Birth Panchang Coordinates)</div>
        <table>
            <tr>
                <th width="20%">वैदिक तिथि</th>
                <td width="30%"><strong>{{ $panchang['tithi'] }}</strong></td>
                <th width="20%">नक्षत्र व स्वामी</th>
                <td width="30%"><strong>{{ $panchang['nakshatra'] }}</strong> ({{ $panchang['nakshatraLord'] }})</td>
            </tr>
            <tr>
                <th>चन्द्र राशि</th>
                <td><strong>{{ $panchang['moonSign'] }}</strong></td>
                <th>राशि स्वामी</th>
                <td>{{ $panchang['moonSignLord'] }}</td>
            </tr>
            <tr>
                <th>दैनिक योग</th>
                <td>{{ $panchang['yoga'] }}</td>
                <th>करण</th>
                <td>{{ $panchang['karana'] }}</td>
            </tr>
            <tr>
                <th>विक्रम संवत्</th>
                <td>{{ $panchang['vikramSamvat'] }}</td>
                <th>अयनांश गणना</th>
                <td>चित्रपक्षीय लाहिड़ी ({{ $ayanamsha }}°)</td>
            </tr>
        </table>

        <div class="remedy-box">
            <strong>🛕 श्री काशी विश्वनाथ संकल्प घोषणा:</strong><br>
            यह कुण्डली वैदिक ऋषि पाराशर होरा शास्त्र के सिद्धांतों पर आधारित है। जातक के कर्म, भाग्य, स्वास्थ्य, वैवाहिक सुख एवं ग्रह दशाओं का पूर्ण विश्लेषण आगामी पृष्ठों में प्रस्तुत है।
        </div>

        <div class="footer">पृष्ठ १ / १० • OnlinePuja.live • प्रमाणित वैदिक कुण्डली प्रणाली</div>
    </div>

    <!-- ================= PAGE 2: AVAKHADA CHAKRA ================= -->
    <div class="page">
        <div class="page-header-strip">
            <div class="left">OnlinePuja.live • {{ $name }}</div>
            <div class="right">पृष्ठ २ / १० • अवकहड़ा चक्र एवं अष्टकूट तत्व</div>
        </div>

        <div class="section-title">अवकहड़ा चक्र एवं मूल तत्व (Avakhada Chakra & Primary Matrix)</div>
        <p style="font-size: 9.5px; color: #4B5563; margin-top: 0;">अवकहड़ा चक्र जातक के जन्म नक्षत्र एवं चन्द्र राशि के आधार पर मानसिक स्वभाव, शारीरिक प्रकृति एवं कुल गुणों का निर्धारण करता है:</p>

        <table>
            <tr>
                <th width="25%">वर्ण (Varna)</th>
                <td width="25%"><strong>{{ $avakhada['varna'] }}</strong></td>
                <th width="25%">वश्य (Vashya)</th>
                <td width="25%"><strong>{{ $avakhada['vashya'] }}</strong></td>
            </tr>
            <tr>
                <th>योनि (Yoni)</th>
                <td><strong>{{ $avakhada['yoni'] }}</strong></td>
                <th>गण (Gana)</th>
                <td><strong>{{ $avakhada['gana'] }}</strong></td>
            </tr>
            <tr>
                <th>नाड़ी (Nadi)</th>
                <td><strong>{{ $avakhada['nadi'] }}</strong></td>
                <th>तत्व (Element)</th>
                <td><strong>{{ $avakhada['element'] }}</strong></td>
            </tr>
            <tr>
                <th>जन्म लग्न</th>
                <td><strong>{{ $avakhada['lagnaSign'] }}</strong></td>
                <th>लग्न स्वामी</th>
                <td><strong>{{ $avakhada['lagnaLord'] }}</strong></td>
            </tr>
            <tr>
                <th>चन्द्र राशि स्वामी</th>
                <td><strong>{{ $avakhada['rashiLord'] }}</strong></td>
                <th>सूर्य राशि</th>
                <td><strong>{{ $planets['Sun (सूर्य)']['sign'] }}</strong></td>
            </tr>
        </table>

        <div class="section-title">अष्टकूट गुण विश्लेषण एवं स्वभाव विचार</div>
        <div class="house-block">
            <div class="house-title">१. वर्ण एवं कर्म स्वभाव (Mental Constitution & Karma)</div>
            <div class="house-text">जातक का वर्ण <strong>{{ $avakhada['varna'] }}</strong> है, जो कर्तव्यनिष्ठा, कर्मशीलता एवं समाज में बौद्धिक व नेतृत्वकारी भूमिका को दर्शाता है। जीवन में स्वतंत्र निर्णय लेने की क्षमता प्रबल रहेगी।</div>
        </div>
        <div class="house-block">
            <div class="house-title">२. गण एवं सामाजिक तालमेल (Temperament & Interactions)</div>
            <div class="house-text">जातक का जन्म <strong>{{ $avakhada['gana'] }}</strong> में हुआ है। यह सात्विक सोच, धर्मपरायणता एवं मित्रों व परिवारजनों के साथ सौहार्दपूर्ण व्यवहार को प्रकट करता है। जीवन में संकट के समय भी धैर्य बना रहेगा।</div>
        </div>
        <div class="house-block">
            <div class="house-title">३. नाड़ी एवं स्वास्थ्य प्रकृति (Ayurvedic Tridosha & Vitality)</div>
            <div class="house-text">जातक की नाड़ी <strong>{{ $avakhada['nadi'] }}</strong> है। वात, पित्त व कफ का संतुलन बनाए रखने हेतु नित्य प्रातः तांबे के पात्र का जल ग्रहण करना तथा सूर्य नमस्कार का अभ्यास करना शुभफलदायी सिद्ध होगा।</div>
        </div>
        <div class="house-block">
            <div class="house-title">४. योनि एवं आंतरिक ऊर्जा (Psychological Drive & Harmony)</div>
            <div class="house-text">योनि <strong>{{ $avakhada['yoni'] }}</strong> होने से कार्यस्थल पर तीव्र गति से निर्णय लेने तथा कठिन परिस्थितियों में भी शांति बनाए रखने की क्षमता प्राप्त होती है।</div>
        </div>

        <div class="footer">पृष्ठ २ / १० • OnlinePuja.live • प्रमाणित वैदिक कुण्डली प्रणाली</div>
    </div>

    <!-- ================= PAGE 3: LAGNA KUNDALI CHAKRA (D-1) ================= -->
    <div class="page">
        <div class="page-header-strip">
            <div class="left">OnlinePuja.live • {{ $name }}</div>
            <div class="right">पृष्ठ ३ / १० • जन्म लग्न कुण्डली (D-1 Rashi Chakra)</div>
        </div>

        <div class="section-title">जन्म लग्न चक्र (D-1 Janma Lagna Chart)</div>
        <p style="font-size: 9.5px; color: #4B5563; margin-top: 0; text-align: center;">लग्न भाव आत्मा के भौतिक प्रकटीकरण, जीवन ऊर्जा, स्वास्थ्य एवं मूल भाग्य का मुख्य केंद्र है:</p>

        <div style="text-align: center; margin: 6px 0;">
            {!! $svgD1 !!}
        </div>

        <div class="section-title">द्वादश भाव एवं ग्रह स्थिति विवरण (House Distribution Summary)</div>
        <table>
            <thead>
                <tr>
                    <th width="15%">भाव (House)</th>
                    <th width="30%">राशि (Rashi in House)</th>
                    <th width="25%">स्थित ग्रह (Occupying Planets)</th>
                    <th width="30%">भाव फल प्रभाव (Core Astrological Impact)</th>
                </tr>
            </thead>
            <tbody>
                @for($h = 1; $h <= 6; $h++)
                @php
                    $hSign = (($lagna['signNum'] + $h - 2) % 12) + 1;
                    $occupants = [];
                    foreach($planets as $pn => $pd) {
                        if($pd['house'] == $h) $occupants[] = $pn;
                    }
                @endphp
                <tr>
                    <td><strong>भाव {{ $h }}</strong></td>
                    <td>राशि {{ $hSign }}</td>
                    <td><strong style="color: #B91C1C;">{{ !empty($occupants) ? implode(', ', $occupants) : 'शुभ रिक्त (Shubha)' }}</strong></td>
                    <td>{{ $h == 1 ? 'व्यक्तित्व, आरोग्य व मान-प्रतिष्ठा' : ($h == 2 ? 'धन संचय, वाणी व कुटुम्ब सुख' : ($h == 3 ? 'पराक्रम, भ्रातृ सुख व पुरुषार्थ' : ($h == 4 ? 'माता, भूमि, वाहन व गृह सौख्य' : ($h == 5 ? 'विद्या, बुद्धि, संतान व पूर्व पुण्य' : 'रोग, ऋण, शत्रु पर विजय')))) }}</td>
                </tr>
                @endfor
            </tbody>
        </table>

        <div class="footer">पृष्ठ ३ / १० • OnlinePuja.live • प्रमाणित वैदिक कुण्डली प्रणाली</div>
    </div>

    <!-- ================= PAGE 4: NAVAMSHA (D-9) & CHANDRA KUNDALI ================= -->
    <div class="page">
        <div class="page-header-strip">
            <div class="left">OnlinePuja.live • {{ $name }}</div>
            <div class="right">पृष्ठ ४ / १० • नवमांश चक्र (D-9) एवं चन्द्र कुण्डली</div>
        </div>

        <div class="section-title">नवमांश कुण्डली (D-9 Navamsha Chakra — धर्म व भाग्योदय चक्र)</div>
        <p style="font-size: 9.5px; color: #4B5563; margin-top: 0;">महर्षि पराशर के अनुसार लग्न कुण्डली शरीर है तो नवमांश उसकी आत्मा है। यह वैवाहिक सौख्य एवं ३० वर्ष की आयु के उपरांत भाग्योदय का सूचक है:</p>

        <div style="text-align: center; margin: 6px 0;">
            {!! $svgD9 !!}
        </div>

        <div class="section-title">नवमांश फल एवं भाग्योदय विश्लेषण</div>
        <div class="house-block">
            <div class="house-title">★ दाम्पत्य जीवन एवं जीवनसाथी का स्वरूप (Marital Harmony & Spouse Nature)</div>
            <div class="house-text">नवमांश चक्र में ग्रहों की स्थिति यह संकेत करती है कि जातक को सुसंस्कृत, धर्मनिष्ठ एवं विचारशील जीवनसाथी प्राप्त होगा। परस्पर सम्मान व आध्यात्मिक तालमेल से गृहस्थ जीवन में सुख-समृद्धि बनी रहेगी।</div>
        </div>
        <div class="house-block">
            <div class="house-title">★ भाग्योदय काल एवं आध्यात्मिक प्रगति (Spiritual & Financial Elevation)</div>
            <div class="house-text">३० वर्ष की आयु के पश्चात भाग्य में अप्रत्याशित प्रगति होगी। नवमांश में ग्रहों का शुभ प्रभाव जातक को समाज में स्थायी आदर, व्यापारिक उन्नति एवं तीर्थाटन का शुभ अवसर प्रदान करता है।</div>
        </div>

        <div class="footer">पृष्ठ ४ / १० • OnlinePuja.live • प्रमाणित वैदिक कुण्डली प्रणाली</div>
    </div>

    <!-- ================= PAGE 5: GRAHA SPASHTA (PLANETARY DEGREES) ================= -->
    <div class="page">
        <div class="page-header-strip">
            <div class="left">OnlinePuja.live • {{ $name }}</div>
            <div class="right">पृष्ठ ५ / १० • ग्रह स्पष्ट एवं खगोलीय स्थिति</div>
        </div>

        <div class="section-title">ग्रह स्पष्ट सारणी (Planetary Longitudes & Astrological Dignities)</div>
        <p style="font-size: 9.5px; color: #4B5563; margin-top: 0;">जन्म समय पर नवग्रहों की निरयण भोगांश डिग्री, राशि, भाव एवं नक्षत्र स्थिति:</p>

        <table>
            <thead>
                <tr>
                    <th>ग्रह (Planet)</th>
                    <th>भोगांश (Degree)</th>
                    <th>राशि (Zodiac Sign)</th>
                    <th>भाव (House)</th>
                    <th>नक्षत्र व चरण (Nakshatra)</th>
                    <th>अवस्था व बल (State/Dignity)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($planets as $pName => $p)
                <tr>
                    <td><strong>{{ $pName }}</strong></td>
                    <td>{{ $p['degree'] }}°</td>
                    <td>{{ $p['sign'] }}</td>
                    <td>भाव {{ $p['house'] }}</td>
                    <td>{{ $p['nakshatra'] }} (च-{{ $p['pada'] }})</td>
                    <td>
                        @if($p['isRetrograde'])
                            <span class="badge badge-amber">वक्री (Vakri)</span>
                        @elseif(str_contains($p['dignity'], 'उच्च'))
                            <span class="badge badge-green">{{ $p['dignity'] }}</span>
                        @elseif(str_contains($p['dignity'], 'नीच'))
                            <span class="badge badge-red">{{ $p['dignity'] }}</span>
                        @else
                            <span class="badge badge-blue">{{ $p['dignity'] }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-title">ग्रह शक्ति एवं दृष्टि विचार (Planetary Strength & Aspects)</div>
        <div class="house-block">
            <div class="house-title">सूर्य (Sun) — आत्मकारक व प्रतिष्ठा प्रदाता</div>
            <div class="house-text">सूर्य देव का प्रभाव नेतृत्व शक्ति, पिता से सहयोग एवं आत्म-सम्मान में वृद्धि करता है। सरकारी कार्यों एवं प्रबंधन में सफलता प्राप्त होती है।</div>
        </div>
        <div class="house-block">
            <div class="house-title">गुरु (Jupiter) — ज्ञान, विवेक व भाग्य कारक</div>
            <div class="house-text">देवगुरु बृहस्पति की दृष्टि धर्म भाव व ज्ञान भाव को पोषित कर रही है। यह जातक को सत्संगति, उच्च शिक्षा एवं अध्यात्म में रुचि प्रदान करती है।</div>
        </div>
        <div class="house-block">
            <div class="house-title">शनि (Saturn) — न्याय, धैर्य व कर्म प्रदाता</div>
            <div class="house-text">कर्मफलदाता शनि देव जातक को अनुशासन, श्रमनिष्ठा एवं दीर्घकालिक सफलता का वरदान देते हैं। कठिनाइयों के पश्चात भी स्थायी विजय प्राप्त होती है।</div>
        </div>

        <div class="footer">पृष्ठ ५ / १० • OnlinePuja.live • प्रमाणित वैदिक कुण्डली प्रणाली</div>
    </div>

    <!-- ================= PAGE 6: 12-BHAV PREDICTIONS ================= -->
    <div class="page">
        <div class="page-header-strip">
            <div class="left">OnlinePuja.live • {{ $name }}</div>
            <div class="right">पृष्ठ ६ / १० • द्वादश भाव विस्तृत जीवन फल</div>
        </div>

        <div class="section-title">द्वादश भाव विस्तृत जीवन फल (12 Houses Comprehensive Forecast)</div>
        <p style="font-size: 9.5px; color: #4B5563; margin-top: 0;">वैदिक ज्योतिष के अनुसार कुण्डली के १२ भाव जातक के सम्पूर्ण जीवन आयामों का प्रतिनिधित्व करते हैं:</p>

        @foreach($housePredictions as $hNum => $hp)
        <div class="house-block" style="margin-bottom: 5px; padding: 4px 6px;">
            <div class="house-title">{{ $hp['name'] }} — {{ $hp['theme'] }}</div>
            <div class="house-text">{{ $hp['desc'] }}</div>
        </div>
        @endforeach

        <div class="footer">पृष्ठ ६ / १० • OnlinePuja.live • प्रमाणित वैदिक कुण्डली प्रणाली</div>
    </div>

    <!-- ================= PAGE 7: VIMSHOTTARI MAHADASHA ================= -->
    <div class="page">
        <div class="page-header-strip">
            <div class="left">OnlinePuja.live • {{ $name }}</div>
            <div class="right">पृष्ठ ७ / १० • विंशोत्तरी महादशा चक्र</div>
        </div>

        <div class="section-title">विंशोत्तरी महादशा काल चक्र (120-Year Parashari Cycle)</div>
        <p style="font-size: 9.5px; color: #4B5563; margin-top: 0;">चन्द्रमा के जन्म नक्षत्र भोगांश से आरम्भ होकर १२० वर्षों की पूर्ण विंशोत्तरी दशा का कालानुक्रम:</p>

        <table>
            <thead>
                <tr>
                    <th>महादशा स्वामी</th>
                    <th>अवधि (Duration)</th>
                    <th>प्रारम्भ तिथि (Start Date)</th>
                    <th>समाप्ति तिथि (End Date)</th>
                    <th>स्थिति (Current Status)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dashas as $d)
                <tr @if($d['isCurrent']) style="background-color: #FEF3C7; font-weight: bold;" @endif>
                    <td><strong>{{ $d['lord'] }} महादशा</strong></td>
                    <td>{{ $d['duration'] }}</td>
                    <td>{{ $d['start'] }}</td>
                    <td>{{ $d['end'] }}</td>
                    <td>
                        @if($d['isCurrent'])
                            <span class="badge badge-green">★ वर्तमान में सक्रिय (Active Now)</span>
                        @elseif(strtotime($d['end']) < time())
                            <span class="badge badge-amber">व्यतीत (Completed)</span>
                        @else
                            <span class="badge badge-blue">आगामी (Upcoming)</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-title">वर्तमान महादशा फल विश्लेषण (Current Active Dasha Forecast: {{ $activeDasha }})</div>
        <div class="house-block">
            <div class="house-title">★ वर्तमान कालखंड का मुख्य प्रभाव एवं दिशा</div>
            <div class="house-text">वर्तमान में जातक पर <strong>{{ $activeDasha }}</strong> की महादशा का प्रभाव चल रहा है। यह कालखंड जीवन में महत्वपूर्ण परिवर्तन, कार्यक्षेत्र में नए अवसर एवं आध्यात्मिक उत्थान की दिशा तय करेगा। इस अवधि में एकाग्रता बनाए रखना तथा अपने इष्ट देव की नित्य आराधना करना सर्वोत्तम फल प्रदान करेगा।</div>
        </div>

        <div class="footer">पृष्ठ ७ / १० • OnlinePuja.live • प्रमाणित वैदिक कुण्डली प्रणाली</div>
    </div>

    <!-- ================= PAGE 8: DOSHA NIRNAYA ================= -->
    <div class="page">
        <div class="page-header-strip">
            <div class="left">OnlinePuja.live • {{ $name }}</div>
            <div class="right">पृष्ठ ८ / १० • दोष निर्णय एवं शान्ति विधान</div>
        </div>

        <div class="section-title">प्रमुख वैदिक दोष निर्णय (Dosha Nirnaya & Astrological Audits)</div>
        <p style="font-size: 9.5px; color: #4B5563; margin-top: 0;">विवाह, स्वास्थ्य, संतान एवं करियर में विघ्न उत्पन्न करने वाले प्रमुख दोषों का शास्त्रीय परीक्षण:</p>

        <table>
            <tr>
                <th width="28%">मांगलिक दोष (Mangal Dosha)</th>
                <td width="72%">
                    @if($doshas['manglik']['hasDosha'])
                        <span class="badge badge-red">मांगलिक दोष विद्यमान (भाव {{ $doshas['manglik']['house'] }})</span>
                        <div style="font-size: 9px; color: #4B5563; margin-top: 3px;">मंगल देव कुण्डली के {{ $doshas['manglik']['house'] }}वें भाव में स्थित हैं। वैवाहिक सौख्य हेतु कुम्भ विवाह अथवा मंगल शान्ति भात पूजा करवाना श्रेयस्कर है।</div>
                    @else
                        <span class="badge badge-green">मांगलिक दोष मुक्त (No Kuja Dosha)</span>
                        <div style="font-size: 9px; color: #4B5563; margin-top: 3px;">कुण्डली मांगलिक दोष से सर्वथा मुक्त है। वैवाहिक जीवन निर्विघ्न एवं कल्याणकारी रहेगा।</div>
                    @endif
                </td>
            </tr>
            <tr>
                <th>कालसर्प योग (Kaal Sarp Yoga)</th>
                <td>
                    @if($doshas['kaalsarp']['hasDosha'])
                        <span class="badge badge-amber">{{ $doshas['kaalsarp']['status'] }}</span>
                        <div style="font-size: 9px; color: #4B5563; margin-top: 3px;">राहु एवं केतु की विशेष धुरी के कारण कार्यक्षेत्र में कभी-कभी विलम्ब की स्थिति बन सकती है। त्र्यम्बकेश्वर अथवा काशी में नागबलि / रुद्राभिषेक से शान्ति होती है।</div>
                    @else
                        <span class="badge badge-green">कालसर्प योग मुक्त (Free from Kaal Sarp)</span>
                        <div style="font-size: 9px; color: #4B5563; margin-top: 3px;">सभी ग्रह राहु-केतु की परिधि से मुक्त हैं। कार्यों में अवरोध का कोई खगोलीय योग नहीं है।</div>
                    @endif
                </td>
            </tr>
            <tr>
                <th>शनि साढ़े साती एवं ढैय्या</th>
                <td>
                    <span class="badge badge-blue">{{ $doshas['sadeSati'] }}</span>
                    <div style="font-size: 9px; color: #4B5563; margin-top: 3px;">शनि देव कर्मानुसार फल प्रदाता हैं। नित्य दशरथकृत शनि स्तोत्र अथवा हनुमान चालीसा का पाठ रक्षा कवच का कार्य करता है।</div>
                </td>
            </tr>
            <tr>
                <th>पितृ दोष विचार</th>
                <td>
                    <span class="badge badge-green">शुभ स्थिति (No Severe Affliction)</span>
                    <div style="font-size: 9px; color: #4B5563; margin-top: 3px;">सूर्य एवं नवम भाव पर कोई क्रूर पाप प्रभाव नहीं है। अमावस्या पर पूर्वजों के निमित्त दीपदान एवं जल तर्पण शुभ रहेगा।</div>
                </td>
            </tr>
        </table>

        <div class="section-title">दोष शान्ति हेतु विशिष्ट वैदिक उपाय</div>
        <div class="house-block">
            <div class="house-title">१. काशी विश्वनाथ महा रुद्राभिषेक पूजा</div>
            <div class="house-text">द्वादश ज्योतिर्लिंगों में प्रधान श्री काशी विश्वनाथ धाम में अपने नाम व गोत्र से रुद्राभिषेक करवाने से सभी नवग्रह दोष एवं कालसर्प बाधाएं स्वतः शांत हो जाती हैं।</div>
        </div>
        <div class="house-block">
            <div class="house-title">२. महामृत्युंजय अनुष्ठान एवं जप</div>
            <div class="house-text">अकाल मृत्यु भय, असाध्य रोग निवारण तथा जीवन में आध्यात्मिक रक्षा कवच हेतु महामृत्युंजय मंत्र का नित्य ११ अथवा २१ बार जप करें।</div>
        </div>

        <div class="footer">पृष्ठ ८ / १० • OnlinePuja.live • प्रमाणित वैदिक कुण्डली प्रणाली</div>
    </div>

    <!-- ================= PAGE 9: LUCKY ELEMENTS & GEMS ================= -->
    <div class="page">
        <div class="page-header-strip">
            <div class="left">OnlinePuja.live • {{ $name }}</div>
            <div class="right">पृष्ठ ९ / १० • शुभ तत्व, रत्न एवं रुद्राक्ष विचार</div>
        </div>

        <div class="section-title">जातक के शुभ एवं भाग्यशाली तत्व (Auspicious Cosmic Elements)</div>
        <p style="font-size: 9.5px; color: #4B5563; margin-top: 0;">लग्न एवं राशि के आधार पर निर्धारित अनुकूल ब्रह्मांडीय तरंगें एवं सहायक तत्व:</p>

        <table>
            <tr>
                <th width="30%">भाग्यशाली रत्न (Lucky Gemstone)</th>
                <td width="70%"><strong style="color: #92400E;">{{ $lucky['gemstone'] }}</strong></td>
            </tr>
            <tr>
                <th>धारण करने की धातु (Metal)</th>
                <td>{{ $lucky['metal'] }}</td>
            </tr>
            <tr>
                <th>धारण करने की अंगुली (Finger)</th>
                <td>{{ $lucky['finger'] }}</td>
            </tr>
            <tr>
                <th>अनुकूल रुद्राक्ष (Sacred Rudraksha)</th>
                <td><strong>{{ $lucky['rudraksha'] }}</strong></td>
            </tr>
            <tr>
                <th>भाग्यशाली अंक (Lucky Numbers)</th>
                <td><strong>{{ $lucky['numbers'] }}</strong></td>
            </tr>
            <tr>
                <th>अनुकूल रंग (Favorable Colors)</th>
                <td>{{ $lucky['colors'] }}</td>
            </tr>
            <tr>
                <th>शुभ वार (Auspicious Days)</th>
                <td>{{ $lucky['days'] }}</td>
            </tr>
            <tr>
                <th>इष्ट देवता (Ishta Devta)</th>
                <td><strong style="color: #B91C1C;">{{ $lucky['ishtaDevta'] }}</strong></td>
            </tr>
        </table>

        <div class="section-title">रत्न धारण एवं रुद्राक्ष विधि</div>
        <div class="house-block">
            <div class="house-title">★ रत्न धारण नियम एवं सावधानी</div>
            <div class="house-text">रत्न सदैव अभिमंत्रित एवं प्राण-प्रतिष्ठित करवाकर ही शुक्ल पक्ष के शुभ मुहूर्त में धारण करना चाहिए। रत्न को धारण करने से पूर्व कच्चे दूध व गंगाजल से स्नान कराएं।</div>
        </div>
        <div class="house-block">
            <div class="house-title">★ रुद्राक्ष महिमा एवं धारण संस्कार</div>
            <div class="house-text"><strong>{{ $lucky['rudraksha'] }}</strong> साक्षात् शिव स्वरूप है। इसे लाल या पीले रेशमी धागे में गूंथकर सोमवार के दिन <em>'ॐ नमः शिवाय'</em> मंत्र का १०८ बार जप कर कंठ में धारण करें।</div>
        </div>

        <div class="footer">पृष्ठ ९ / १० • OnlinePuja.live • प्रमाणित वैदिक कुण्डली प्रणाली</div>
    </div>

    <!-- ================= PAGE 10: REMEDIES & TEMPLE BLESSING ================= -->
    <div class="page">
        <div class="page-header-strip">
            <div class="left">OnlinePuja.live • {{ $name }}</div>
            <div class="right">पृष्ठ १० / १० • वैदिक अचूक उपाय एवं काशी आशीर्वाद</div>
        </div>

        <div class="section-title">नित्य साधना एवं दैनिक महामंत्र (Daily Spiritual Sadhana)</div>
        <div class="house-block">
            <div class="house-title">१. दैनिक इष्ट महामंत्र जप</div>
            <div class="house-text">
                <em>॥ ॐ नमः शिवाय शुभं शुभं कुरू कुरू शिवाय नमः ॐ ॥</em><br>
                प्रातः स्नान के उपरांत पूर्व दिशा की ओर मुख कर रुद्राक्ष माला से कम से कम १ माला (१०८ जप) नित्य करें।
            </div>
        </div>
        <div class="house-block">
            <div class="house-title">२. सूर्य अर्घ्य एवं गायत्री मंत्र</div>
            <div class="house-text">
                नित्य प्रातः तांबे के लोटे में शुद्ध जल, रोली, अक्षत एवं लाल पुष्प डालकर <em>'ॐ सूर्याय नमः'</em> बोलते हुए भगवान भुवन भास्कर को अर्घ्य प्रदान करें।
            </div>
        </div>

        <div class="section-title">पावन श्री काशी विश्वनाथ तीर्थ संकल्प एवं सेवा</div>
        <div class="cover-box" style="margin-top: 8px; padding: 15px 12px; background: #FFFBEB;">
            <div style="font-size: 24px; color: #D97706;">🛕 🔱 🚩</div>
            <h3 style="color: #92400E; margin: 4px 0;">श्री काशी विश्वनाथ धाम, वाराणसी</h3>
            <p style="font-size: 10px; color: #78350F; margin: 2px 0 10px 0;">Certified Vedic Devotional Services • Direct Temple Pujas by Verified Kashi Pandits</p>

            <p style="font-size: 9.5px; color: #374151; line-height: 1.4; text-align: left;">
                अपने एवं अपने परिवार के कल्याण, ग्रह शान्ति, स्वास्थ्य, दीर्घायु एवं व्यापारिक समृद्धि हेतु श्री काशी विश्वनाथ धाम व दशाश्वमेध घाट पर अपने <strong>नाम व गोत्र संकल्प</strong> से प्रामाणिक पूजा बुक करें:
            </p>
            <div style="text-align: center; margin: 10px 0;">
                <div style="display: inline-block; background: #D97706; color: #FFFFFF; font-weight: bold; padding: 8px 18px; border-radius: 6px; font-size: 11px;">
                    👉 https://onlinepuja.live/pujas
                </div>
            </div>
            <p style="font-size: 9px; color: #6B7280; margin-top: 6px;">
                व्हाट्सएप सेवा केंद्र: +91 70071 58014 • सहायता ईमेल: support@onlinepuja.live<br>
                एंड्रॉइड ऐप डाउनलोड करें: <strong>OnlinePuja Devotee App</strong> (Google Play Store)
            </p>
        </div>

        <div class="footer">पृष्ठ १० / १० • OnlinePuja.live • प्रमाणित वैदिक कुण्डली प्रणाली • सर्वे भवन्तु सुखिनः</div>
    </div>

</body>
</html>
