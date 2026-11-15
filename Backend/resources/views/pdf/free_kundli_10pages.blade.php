<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Complete Vedic Horoscope - {{ $name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 14mm 10mm 14mm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
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
            letter-spacing: 0.5px;
        }
        .brand-title {
            font-size: 20px;
            font-weight: 800;
            color: #92400E;
            text-transform: uppercase;
            letter-spacing: 1.5px;
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
            font-size: 11px;
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
            width: 140px;
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
            <div class="shloka">OM SHREE GANESHAYA NAMAHA • OM NAMAH SHIVAYA • OM NAMO BHAGAVATE VASUDEVAYA</div>
            <div class="brand-title">OnlinePuja.live</div>
            <div class="subtitle">Shree Kashi Vishwanath Dham, Varanasi • Certified Drik Ganita Vedic Horoscope</div>
        </div>

        <div class="cover-box">
            <h1 style="color: #92400E; font-size: 22px; margin: 4px 0;">COMPLETE VEDIC BIRTH HOROSCOPE</h1>
            <p style="color: #B45309; font-size: 11px; margin: 2px 0 12px 0;">Computed with Chitra Paksha Lahiri Ayanamsha ({{ $ayanamsha }}°) Precision Mathematics</p>

            <div class="devotee-card">
                <div class="card-row"><span class="card-label">Devotee Name:</span> <span class="card-val" style="color: #B91C1C; font-size: 12px;">{{ $name }}</span></div>
                <div class="card-row"><span class="card-label">Date of Birth:</span> <span class="card-val">{{ date('d F Y', strtotime($dob)) }}</span></div>
                <div class="card-row"><span class="card-label">Time of Birth:</span> <span class="card-val">{{ date('h:i A', strtotime($tob)) }} (IST)</span></div>
                <div class="card-row"><span class="card-label">Place of Birth:</span> <span class="card-val">{{ $pob }}</span></div>
                <div class="card-row"><span class="card-label">Coordinates:</span> <span class="card-val">{{ round($lat, 4) }} deg N, {{ round($lon, 4) }} deg E</span></div>
                <div class="card-row"><span class="card-label">Ascendant (Lagna):</span> <span class="card-val">{{ $lagna['sign'] }} ({{ $lagna['degree'] }} deg)</span></div>
                <div class="card-row"><span class="card-label">Moon Sign (Rashi):</span> <span class="card-val">{{ $panchang['moonSign'] }}</span></div>
                <div class="card-row"><span class="card-label">Birth Nakshatra:</span> <span class="card-val">{{ $panchang['nakshatra'] }} (Pada {{ $panchang['nakshatraPada'] }})</span></div>
            </div>
        </div>

        <div class="section-title">Birth Panchang Coordinates (Jātaka Vivaraṇa)</div>
        <table>
            <tr>
                <th width="20%">Vedic Tithi</th>
                <td width="30%"><strong>{{ $panchang['tithi'] }}</strong></td>
                <th width="20%">Nakshatra & Lord</th>
                <td width="30%"><strong>{{ $panchang['nakshatra'] }}</strong> (Lord: {{ $panchang['nakshatraLord'] }})</td>
            </tr>
            <tr>
                <th>Moon Sign (Rashi)</th>
                <td><strong>{{ $panchang['moonSign'] }}</strong></td>
                <th>Rashi Lord</th>
                <td>{{ $panchang['moonSignLord'] }}</td>
            </tr>
            <tr>
                <th>Nitya Yoga</th>
                <td>{{ $panchang['yoga'] }}</td>
                <th>Karana</th>
                <td>{{ $panchang['karana'] }}</td>
            </tr>
            <tr>
                <th>Vikram Samvat</th>
                <td>{{ $panchang['vikramSamvat'] }}</td>
                <th>Ayanamsha</th>
                <td>Chitra Paksha Lahiri ({{ $ayanamsha }} deg)</td>
            </tr>
        </table>

        <div class="remedy-box">
            <strong>Kashi Vishwanath Sankalp Proclamation:</strong><br>
            This horoscope is prepared according to the classical principles of Maharishi Parashara Brihat Hora Shastra. The comprehensive analysis of life destiny, planetary coordinates, dashas, doshas, and Vedic remedies is presented in the following pages.
        </div>

        <div class="footer">Page 1 of 10 • OnlinePuja.live • Certified Vedic Horoscope System</div>
    </div>

    <!-- ================= PAGE 2: AVAKHADA CHAKRA ================= -->
    <div class="page">
        <div class="page-header-strip">
            <div class="left">OnlinePuja.live • {{ $name }}</div>
            <div class="right">Page 2 of 10 • Avakhada Chakra & Ashtakoota Fundamentals</div>
        </div>

        <div class="section-title">Avakhada Chakra & Primary Cosmic Matrix</div>
        <p style="font-size: 9.5px; color: #4B5563; margin-top: 0;">The Avakhada Chakra defines the innate psychological constitution, physical vitality, and natural temperament according to the birth Nakshatra and Moon placement:</p>

        <table>
            <tr>
                <th width="25%">Varna</th>
                <td width="25%"><strong>{{ $avakhada['varna'] }}</strong></td>
                <th width="25%">Vashya</th>
                <td width="25%"><strong>{{ $avakhada['vashya'] }}</strong></td>
            </tr>
            <tr>
                <th>Yoni</th>
                <td><strong>{{ $avakhada['yoni'] }}</strong></td>
                <th>Gana</th>
                <td><strong>{{ $avakhada['gana'] }}</strong></td>
            </tr>
            <tr>
                <th>Nadi</th>
                <td><strong>{{ $avakhada['nadi'] }}</strong></td>
                <th>Element (Tatva)</th>
                <td><strong>{{ $avakhada['element'] }}</strong></td>
            </tr>
            <tr>
                <th>Ascendant (Lagna)</th>
                <td><strong>{{ $avakhada['lagnaSign'] }}</strong></td>
                <th>Lagna Lord</th>
                <td><strong>{{ $avakhada['lagnaLord'] }}</strong></td>
            </tr>
            <tr>
                <th>Moon Sign Lord</th>
                <td><strong>{{ $avakhada['rashiLord'] }}</strong></td>
                <th>Sun Sign (Surya)</th>
                <td><strong>{{ $planets['Sun (Surya)']['sign'] }}</strong></td>
            </tr>
        </table>

        <div class="section-title">Ashtakoota Core Element Analysis</div>
        <div class="house-block">
            <div class="house-title">1. Varna & Temperament Matrix</div>
            <div class="house-text">The native possesses <strong>{{ $avakhada['varna'] }}</strong> Varna, signifying high devotion to duty, acute intellectual discernment, and natural executive capabilities. You possess the mental resilience to make independent decisions during critical cross-roads.</div>
        </div>
        <div class="house-block">
            <div class="house-title">2. Gana & Social Interaction Dynamics</div>
            <div class="house-text">Born under <strong>{{ $avakhada['gana'] }}</strong> Gana. This reflects virtuous noble instincts, a respectful attitude toward cultural traditions, and peaceful interpersonal relationships. You maintain dignified calm even amidst demanding conditions.</div>
        </div>
        <div class="house-block">
            <div class="house-title">3. Nadi & Ayurvedic Constitution (Tridosha)</div>
            <div class="house-text">Governed by <strong>{{ $avakhada['nadi'] }}</strong> Nadi. Maintaining balance in dietary habits, drinking water from copper vessels in the morning, and practicing daily breathing exercises (Pranayama) will optimize physical longevity and mental vitality.</div>
        </div>
        <div class="house-block">
            <div class="house-title">4. Yoni & Instinctive Energy</div>
            <div class="house-text">Endowed with <strong>{{ $avakhada['yoni'] }}</strong> Yoni. This grants swift adaptability, deep loyalty toward close associates, and steadfast patience when pursuing ambitious long-term goals.</div>
        </div>

        <div class="footer">Page 2 of 10 • OnlinePuja.live • Certified Vedic Horoscope System</div>
    </div>

    <!-- ================= PAGE 3: LAGNA KUNDALI CHAKRA (D-1) ================= -->
    <div class="page">
        <div class="page-header-strip">
            <div class="left">OnlinePuja.live • {{ $name }}</div>
            <div class="right">Page 3 of 10 • Janma Lagna Kundali (D-1 Rashi Chakra)</div>
        </div>

        <div class="section-title">Janma Lagna Kundali Chakra (D-1 Ascendant Chart)</div>
        <p style="font-size: 9.5px; color: #4B5563; margin-top: 0; text-align: center;">The 1st House (Lagna) represents physical vitality, personality, self-expression, and general fortune:</p>

        <div style="text-align: center; margin: 6px 0;">
            {!! $svgD1 !!}
        </div>

        <div class="section-title">House Planetary Distribution Summary</div>
        <table>
            <thead>
                <tr>
                    <th width="15%">House</th>
                    <th width="25%">Sign in House</th>
                    <th width="25%">Occupying Planets</th>
                    <th width="35%">Primary Astrological Significance</th>
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
                    <td><strong>House {{ $h }}</strong></td>
                    <td>Sign {{ $hSign }}</td>
                    <td><strong style="color: #B91C1C;">{{ !empty($occupants) ? implode(', ', $occupants) : 'Auspicious Empty (Shubha)' }}</strong></td>
                    <td>{{ $h == 1 ? 'Self, Vitality & Physical Personality' : ($h == 2 ? 'Accumulated Wealth, Speech & Family' : ($h == 3 ? 'Courage, Siblings & Initiatives' : ($h == 4 ? 'Mother, Property, Vehicles & Domestic Peace' : ($h == 5 ? 'Intellect, Past Merits & Children' : 'Overcoming Debts, Health & Competitive Prowess')))) }}</td>
                </tr>
                @endfor
            </tbody>
        </table>

        <div class="footer">Page 3 of 10 • OnlinePuja.live • Certified Vedic Horoscope System</div>
    </div>

    <!-- ================= PAGE 4: NAVAMSHA (D-9) & DHARMA CHAKRA ================= -->
    <div class="page">
        <div class="page-header-strip">
            <div class="left">OnlinePuja.live • {{ $name }}</div>
            <div class="right">Page 4 of 10 • Navamsha Kundali (D-9 Dharma Chakra)</div>
        </div>

        <div class="section-title">Navamsha Kundali (D-9 Dharma & Fortune Chakra)</div>
        <p style="font-size: 9.5px; color: #4B5563; margin-top: 0;">In Parashari astrology, if the D-1 chart is the seed, the D-9 Navamsha chart is the blooming fruit. It reveals spiritual destiny, marital bliss, and post-30 fortunes:</p>

        <div style="text-align: center; margin: 6px 0;">
            {!! $svgD9 !!}
        </div>

        <div class="section-title">Navamsha Predictions & Spiritual Fortunes</div>
        <div class="house-block">
            <div class="house-title">Marital Harmony & Spouse Characteristics</div>
            <div class="house-text">Planetary placements in the Navamsha chart indicate a cultured, supportive, and spiritually inclined life companion. Mutual respect, shared ethical values, and mutual support will bring lasting contentment to domestic life.</div>
        </div>
        <div class="house-block">
            <div class="house-title">Post-30 Fortune Elevation & Spiritual Trajectory</div>
            <div class="house-text">After reaching 30 years of age, your innate spiritual strengths and professional breakthroughs will accelerate rapidly. Strong planetary harmonics in the 9th division bestow lasting public goodwill, commercial prosperity, and sacred pilgrimage opportunities.</div>
        </div>

        <div class="footer">Page 4 of 10 • OnlinePuja.live • Certified Vedic Horoscope System</div>
    </div>

    <!-- ================= PAGE 5: GRAHA SPASHTA (PLANETARY DEGREES) ================= -->
    <div class="page">
        <div class="page-header-strip">
            <div class="left">OnlinePuja.live • {{ $name }}</div>
            <div class="right">Page 5 of 10 • Planetary Longitudes & Dignities</div>
        </div>

        <div class="section-title">Graha Spashta Table (Planetary Coordinates & Dignities)</div>
        <p style="font-size: 9.5px; color: #4B5563; margin-top: 0;">Astronomical Nirayana Sidereal Longitudes, House Placements, Nakshatras, and Astrological States at the time of birth:</p>

        <table>
            <thead>
                <tr>
                    <th>Planet (Graha)</th>
                    <th>Degree</th>
                    <th>Zodiac Sign</th>
                    <th>House</th>
                    <th>Nakshatra (Pada)</th>
                    <th>Dignity / State</th>
                </tr>
            </thead>
            <tbody>
                @foreach($planets as $pName => $p)
                <tr>
                    <td><strong>{{ $pName }}</strong></td>
                    <td>{{ $p['degree'] }} deg</td>
                    <td>{{ $p['sign'] }}</td>
                    <td>House {{ $p['house'] }}</td>
                    <td>{{ $p['nakshatra'] }} (P-{{ $p['pada'] }})</td>
                    <td>
                        @if($p['isRetrograde'])
                            <span class="badge badge-amber">Retrograde (Vakri)</span>
                        @elseif(str_contains($p['dignity'], 'Exalted'))
                            <span class="badge badge-green">{{ $p['dignity'] }}</span>
                        @elseif(str_contains($p['dignity'], 'Debilitated'))
                            <span class="badge badge-red">{{ $p['dignity'] }}</span>
                        @else
                            <span class="badge badge-blue">{{ $p['dignity'] }}</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-title">Key Planetary Functional Roles</div>
        <div class="house-block">
            <div class="house-title">Sun (Surya) — Soul Karaka & Natural Authority</div>
            <div class="house-text">Governs self-confidence, administrative competence, and fatherly support. Provides energy to undertake high-responsibility roles and gain honorable recognition.</div>
        </div>
        <div class="house-block">
            <div class="house-title">Jupiter (Guru) — Wisdom, Higher Counsel & Grace</div>
            <div class="house-text">The great benefic casts protective rays over your key houses. Encourages higher philosophical learning, righteous conduct, and divine guidance during difficult periods.</div>
        </div>
        <div class="house-block">
            <div class="house-title">Saturn (Shani) — Karmic Duty, Discipline & Endurance</div>
            <div class="house-text">Fosters patient endurance, organizational diligence, and long-term stability. Rewards honest labor with enduring achievements that withstand the test of time.</div>
        </div>

        <div class="footer">Page 5 of 10 • OnlinePuja.live • Certified Vedic Horoscope System</div>
    </div>

    <!-- ================= PAGE 6: 12-BHAV PREDICTIONS ================= -->
    <div class="page">
        <div class="page-header-strip">
            <div class="left">OnlinePuja.live • {{ $name }}</div>
            <div class="right">Page 6 of 10 • 12-Bhava Comprehensive Life Forecast</div>
        </div>

        <div class="section-title">12-Bhava Comprehensive Life Predictions</div>
        <p style="font-size: 9.5px; color: #4B5563; margin-top: 0;">Detailed Vedic forecast for every domain of human experience according to classical Parashari principles:</p>

        @foreach($housePredictions as $hNum => $hp)
        <div class="house-block" style="margin-bottom: 5px; padding: 4px 6px;">
            <div class="house-title">{{ $hp['name'] }} — {{ $hp['theme'] }}</div>
            <div class="house-text">{{ $hp['desc'] }}</div>
        </div>
        @endforeach

        <div class="footer">Page 6 of 10 • OnlinePuja.live • Certified Vedic Horoscope System</div>
    </div>

    <!-- ================= PAGE 7: VIMSHOTTARI MAHADASHA ================= -->
    <div class="page">
        <div class="page-header-strip">
            <div class="left">OnlinePuja.live • {{ $name }}</div>
            <div class="right">Page 7 of 10 • Vimshottari Mahadasha Timeline</div>
        </div>

        <div class="section-title">Vimshottari Mahadasha Timeline (120-Year Parashari Cycle)</div>
        <p style="font-size: 9.5px; color: #4B5563; margin-top: 0;">Calculated from the exact natal Moon longitude and Nakshatra balance spanning the full 120-year Parashari cycle:</p>

        <table>
            <thead>
                <tr>
                    <th>Mahadasha Lord</th>
                    <th>Duration</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dashas as $d)
                <tr @if($d['isCurrent']) style="background-color: #FEF3C7; font-weight: bold;" @endif>
                    <td><strong>{{ $d['lord'] }} Mahadasha</strong></td>
                    <td>{{ $d['duration'] }}</td>
                    <td>{{ $d['start'] }}</td>
                    <td>{{ $d['end'] }}</td>
                    <td>
                        @if($d['isCurrent'])
                            <span class="badge badge-green">★ ACTIVE NOW</span>
                        @elseif(strtotime($d['end']) < time())
                            <span class="badge badge-amber">Completed</span>
                        @else
                            <span class="badge badge-blue">Upcoming</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="section-title">Current Running Mahadasha Forecast: {{ $activeDasha }} Period</div>
        <div class="house-block">
            <div class="house-title">Key Trends & Opportunities in the Current Era</div>
            <div class="house-text">You are currently navigating the planetary cycle of <strong>{{ $activeDasha }}</strong>. This phase brings pivotal shifts in your career trajectory, fresh financial avenues, and heightened spiritual awareness. Focusing on steadfast discipline and honoring daily spiritual commitments will yield maximum prosperity during this time.</div>
        </div>

        <div class="footer">Page 7 of 10 • OnlinePuja.live • Certified Vedic Horoscope System</div>
    </div>

    <!-- ================= PAGE 8: DOSHA NIRNAYA ================= -->
    <div class="page">
        <div class="page-header-strip">
            <div class="left">OnlinePuja.live • {{ $name }}</div>
            <div class="right">Page 8 of 10 • Dosha Nirnaya & Astrological Audits</div>
        </div>

        <div class="section-title">Major Vedic Dosha Nirnaya (Astrological Audits)</div>
        <p style="font-size: 9.5px; color: #4B5563; margin-top: 0;">Classical examination of planetary combinations affecting marriage, health, longevity, and professional growth:</p>

        <table>
            <tr>
                <th width="28%">Manglik Dosha (Kuja Dosha)</th>
                <td width="72%">
                    @if($doshas['manglik']['hasDosha'])
                        <span class="badge badge-red">Manglik Dosha Present in House {{ $doshas['manglik']['house'] }}</span>
                        <div style="font-size: 9px; color: #4B5563; margin-top: 3px;">Mars is positioned in House {{ $doshas['manglik']['house'] }}. Performing Mangal Shanti Puja or Kumbh Vivah before sacred matrimony neutralizes all marital hurdles.</div>
                    @else
                        <span class="badge badge-green">No Manglik Dosha (Kuja Dosha Mukta)</span>
                        <div style="font-size: 9px; color: #4B5563; margin-top: 3px;">Your chart is completely free from Kuja Dosha. Marital life is naturally blessed with auspicious compatibility.</div>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Kaal Sarp Yoga Assessment</th>
                <td>
                    @if($doshas['kaalsarp']['hasDosha'])
                        <span class="badge badge-amber">{{ $doshas['kaalsarp']['status'] }}</span>
                        <div style="font-size: 9px; color: #4B5563; margin-top: 3px;">Planets hemmed along the Rahu-Ketu axis may cause occasional delays in career recognition. Rahu-Ketu Shanti at Trimbakeshwar or Kashi Vishwanath dissolves impediments.</div>
                    @else
                        <span class="badge badge-green">Free from Kaal Sarp Yoga</span>
                        <div style="font-size: 9px; color: #4B5563; margin-top: 3px;">Planetary positions are free from nodal entrapment. Initiatives progress steadily without karmic roadblocks.</div>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Shani Sade Sati & Dhaiya Status</th>
                <td>
                    <span class="badge badge-blue">{{ $doshas['sadeSati'] }}</span>
                    <div style="font-size: 9px; color: #4B5563; margin-top: 3px;">Saturn rewards integrity and ethical conduct. Chanting the Dasharatha Shani Stotram or Hanuman Chalisa serves as an impregnable shield.</div>
                </td>
            </tr>
            <tr>
                <th>Pitru Dosha Audit</th>
                <td>
                    <span class="badge badge-green">Favorable Heritage (No Severe Afflictions)</span>
                    <div style="font-size: 9px; color: #4B5563; margin-top: 3px;">Sun and the 9th House are clear of malefic affliction. Offering tarpan and deepdaan on Amavasya days reinforces ancestral blessings.</div>
                </td>
            </tr>
        </table>

        <div class="section-title">Recommended Sacred Temple Remedies</div>
        <div class="house-block">
            <div class="house-title">1. Kashi Vishwanath Maha Rudrabhishek Puja</div>
            <div class="house-text">Performed in the name and Gotra of the devotee at the supreme Jyotirlinga of Lord Shiva in Kashi. Dissolves malefic planetary transits, pacifies past karmic debts, and grants profound peace of mind.</div>
        </div>
        <div class="house-block">
            <div class="house-title">2. Maha Mrityunjaya Japa Anushthan</div>
            <div class="house-text">Provides divine protection against chronic health afflictions and unexpected hazards. Bestows longevity, vitality, and spiritual fortitude.</div>
        </div>

        <div class="footer">Page 8 of 10 • OnlinePuja.live • Certified Vedic Horoscope System</div>
    </div>

    <!-- ================= PAGE 9: LUCKY ELEMENTS & GEMS ================= -->
    <div class="page">
        <div class="page-header-strip">
            <div class="left">OnlinePuja.live • {{ $name }}</div>
            <div class="right">Page 9 of 10 • Auspicious Elements & Gemstones</div>
        </div>

        <div class="section-title">Auspicious Cosmic Elements & Recommendations</div>
        <p style="font-size: 9.5px; color: #4B5563; margin-top: 0;">Cosmic frequencies, gemstones, and numbers harmonized with your Ascendant and Moon sign:</p>

        <table>
            <tr>
                <th width="30%">Auspicious Gemstone (Ratna)</th>
                <td width="70%"><strong style="color: #92400E;">{{ $lucky['gemstone'] }}</strong></td>
            </tr>
            <tr>
                <th>Wearing Metal</th>
                <td>{{ $lucky['metal'] }}</td>
            </tr>
            <tr>
                <th>Wearing Finger</th>
                <td>{{ $lucky['finger'] }}</td>
            </tr>
            <tr>
                <th>Sacred Rudraksha</th>
                <td><strong>{{ $lucky['rudraksha'] }}</strong></td>
            </tr>
            <tr>
                <th>Lucky Numbers</th>
                <td><strong>{{ $lucky['numbers'] }}</strong></td>
            </tr>
            <tr>
                <th>Favorable Colors</th>
                <td>{{ $lucky['colors'] }}</td>
            </tr>
            <tr>
                <th>Auspicious Days</th>
                <td>{{ $lucky['days'] }}</td>
            </tr>
            <tr>
                <th>Ishta Devta</th>
                <td><strong style="color: #B91C1C;">{{ $lucky['ishtaDevta'] }}</strong></td>
            </tr>
        </table>

        <div class="section-title">Gemstone & Rudraksha Sanctification Guidance</div>
        <div class="house-block">
            <div class="house-title">Gemstone Sanctification (Prana Pratishtha)</div>
            <div class="house-text">Gemstones should be naturally sourced, unheated, and ritually energized on an auspicious Shukla Paksha morning. Purify with raw milk and holy Ganga water before slipping onto the designated finger.</div>
        </div>
        <div class="house-block">
            <div class="house-title">Sacred Rudraksha Wearing Ceremony</div>
            <div class="house-text">The <strong>{{ $lucky['rudraksha'] }}</strong> is an embodiment of Lord Shiva's divine grace. Thread in pure silk and chant <em>'Om Namah Shivaya'</em> 108 times before wearing around the neck on a sacred Monday.</div>
        </div>

        <div class="footer">Page 9 of 10 • OnlinePuja.live • Certified Vedic Horoscope System</div>
    </div>

    <!-- ================= PAGE 10: REMEDIES & TEMPLE BLESSING ================= -->
    <div class="page">
        <div class="page-header-strip">
            <div class="left">OnlinePuja.live • {{ $name }}</div>
            <div class="right">Page 10 of 10 • Vedic Remedies & Kashi Blessing</div>
        </div>

        <div class="section-title">Daily Spiritual Sadhana & Mahamantras</div>
        <div class="house-block">
            <div class="house-title">1. Daily Ishta Mahamantra Chanting</div>
            <div class="house-text">
                <em>OM NAMAH SHIVAYA SHUBHAM SHUBHAM KURU KURU SHIVAYA NAMAH OM</em><br>
                Chant 108 times using a Rudraksha mala every morning facing East following a fresh bath.
            </div>
        </div>
        <div class="house-block">
            <div class="house-title">2. Surya Arghya & Gayatri Mantra</div>
            <div class="house-text">
                Offer clean water from a copper vessel with vermilion (roli), whole rice (akshat), and red flowers to the rising Sun while reciting <em>'Om Suryaya Namaha'</em> for radiant vitality.
            </div>
        </div>

        <div class="section-title">Sacred Kashi Vishwanath Sanctuary Blessing</div>
        <div class="cover-box" style="margin-top: 8px; padding: 15px 12px; background: #FFFBEB;">
            <h3 style="color: #92400E; margin: 4px 0;">Shree Kashi Vishwanath Sanctuary, Varanasi</h3>
            <p style="font-size: 10px; color: #78350F; margin: 2px 0 10px 0;">Certified Vedic Devotional Services • Direct Temple Pujas by Verified Kashi Pandits</p>

            <p style="font-size: 9.5px; color: #374151; line-height: 1.4; text-align: left;">
                For the overall welfare, planetary harmony, health, longevity, and professional elevation of you and your family, book authentic temple pujas with your personal <strong>Name and Gotra Sankalp</strong> performed live from Kashi Vishwanath Dham:
            </p>
            <div style="text-align: center; margin: 10px 0;">
                <div style="display: inline-block; background: #D97706; color: #FFFFFF; font-weight: bold; padding: 8px 18px; border-radius: 6px; font-size: 11px;">
                    https://onlinepuja.live/pujas
                </div>
            </div>
            <p style="font-size: 9px; color: #6B7280; margin-top: 6px;">
                WhatsApp Helpline: +91 70071 58014 • Support Email: support@onlinepuja.live<br>
                Download Devotee App: <strong>OnlinePuja Devotee App</strong> (Google Play Store)
            </p>
        </div>

        <div class="footer">Page 10 of 10 • OnlinePuja.live • Certified Vedic Horoscope System • Sarve Bhavantu Sukhinah</div>
    </div>

</body>
</html>
