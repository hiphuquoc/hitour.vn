<div class="contentShip_item">
    <div id="lich-tau-va-gia-ve-tau-cao-toc-phu-quoc" class="contentShip_item_title" data-toccontent>
        <i class="fa-solid fa-clock"></i>
        <h2>Lịch trình và giá vé tàu {{ $keyWord ?? null }}</h2>
    </div>
    <div class="contentTour_item_text">
        <p><a href="{{ URL::current() }}">{{ !empty($keyWord) ? 'Lịch tàu '.$keyWord : 'Lịch tàu cao tốc' }}</a> bên dưới là lộ trình chính xác được Hitour cập nhật thường xuyên từ hãng tàu. Tuy nhiên, có một số trường hợp do thời tiết, bảo trì,... lịch tàu thay đổi đột xuất sẽ được thông báo riêng cho Quý khách khi đặt vé.</p>
        <p><strong>Giá vé tàu</strong> niêm yết theo bảng bên dưới áp dụng cho khách lẻ. Đối với khách đoàn lớn (20 khách trở lên) và đối tác vui lòng liện hệ <span style="font-size:1.4rem;font-weight:bold;color:rgb(0,123,255);">08.6868.4868</span> để biết thêm chi tiết.</p>
        @php
            /*
                times:time          => [
                    7:20 - 11:20,
                ]
                prices:price_adult  => 
                prices:price_child  => 
                prices:price_old    => 
                prices:price_vip    => 

                => nếu mảng time và các mức giá trùng thì gộp

                prices:date         

            */
            $dataMerge              = [];
            $i                      = 0;
            foreach($item->prices as $price){
                /* check có trùng giờ + giá? */
                /* tạo mảng time để so sánh */
                $tmp            = [];
                foreach($price->times as $time){
                    $tmp[$time->ship_from.' - '.$time->ship_to][]      = $time->time_departure.' - '.$time->time_arrive;
                }
                /* tiến hành check */
                $keyMatch       = '';
                foreach($dataMerge as $key => $itemCheck){
                    /* check giá */
                    if(
                        $itemCheck['price_adult']==$price->price_adult
                        &&$itemCheck['price_child']==$price->price_child
                        &&$itemCheck['price_old']==$price->price_old
                        &&$itemCheck['price_vip']==$price->price_vip
                    ){
                        /* trùng giá => check tiếp giờ tàu */
                        if(json_encode($tmp)==json_encode($itemCheck['time'])){ /* trùng thời gian */
                            $keyMatch   = $key;
                            break;
                        }
                    }
                }
                /* keyMatch tồn tại tức trùng 1 khung giá + thời gian => gộp ngày áp dụng */
                if($keyMatch!=''){
                    $dataMerge[$keyMatch]['date'][]  = date('d/m/Y', strtotime($price->date_start)).' - '.date('d/m/Y', strtotime($price->date_end));
                }else {
                    foreach($price->times as $time){
                        $dataMerge[$i]['time'][$time->ship_from.' - '.$time->ship_to][]    = $time->time_departure.' - '.$time->time_arrive;
                    }
                    $dataMerge[$i]['partner_name']  = $price->partner->name;
                    $dataMerge[$i]['partner_logo']  = $price->partner->company_logo;
                    $dataMerge[$i]['price_adult']   = $price->price_adult;
                    $dataMerge[$i]['price_child']   = $price->price_child;
                    $dataMerge[$i]['price_old']     = $price->price_old;
                    $dataMerge[$i]['price_vip']     = $price->price_vip;
                    $dataMerge[$i]['date'][]        = date('d/m/Y', strtotime($price->date_start)).' - '.date('d/m/Y', strtotime($price->date_end));
                    ++$i;
                }
            }
        @endphp

        <table class="tableContentBorder" style="font-size:0.95rem;">
            <thead>
                <tr>
                    <th>Hãng tàu</th>
                    <th>Khởi hành - cập bến</th>
                    <th>Giá vé</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dataMerge as $data)
                <tr>
                    <td>
                        <div>
                            Phú Quốc Express
                        </div>
                        <div>
                            Ngày áp dụng:<br/>
                            @foreach($data['date'] as $date)
                                <div class="highLight">{{ $date }}</div>
                            @endforeach
                        </div>
                    </td>
                    <td>
                        @foreach($data['time'] as $departure => $time)
                        <div class="oneLine">
                            <h3>{{ $departure }}</h3>
                            @foreach($time as $t)
                                <div>{{ $t }}</div>
                            @endforeach
                        </div>
                        @endforeach
                    </td>
                    <td>
                        <div><span style="font-weight:700;color:rgb(0, 90, 180);font-size:1.1rem;">{{ number_format($data['price_adult']) }}<sup>đ</sup></span> /Người lớn</div>
                        <div><span style="font-weight:700;color:rgb(0, 90, 180);font-size:1.1rem;">{{ number_format($data['price_child']) }}<sup>đ</sup></span> /Trẻ em 6-11</div>
                        <div><span style="font-weight:700;color:rgb(0, 90, 180);font-size:1.1rem;">{{ number_format($data['price_old']) }}<sup>đ</sup></span> /Trên 60</div>
                        <div><span style="font-weight:700;color:rgb(0, 90, 180);font-size:1.1rem;">{{ number_format($data['price_vip']) }}<sup>đ</sup></span> /Vé VIP</div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

    </div>
</div>

<!-- Hãng tàu -->
<div class="contentShip_item">
    <div id="hang-tau-cao-toc-phu-quoc" class="contentShip_item_title" data-tocContent>
        <i class="fa-solid fa-award"></i>
        <h2>Lựa chọn hãng tàu {{ $keyWord ?? 'tàu cao tốc' }} uy tín - chất lượng?</h2>
    </div>
    <div class="contentTour_item_text">
        <p>Việc lựa chọn <strong>hãng tàu cao tốc</strong> phù hợp cho riêng bạn rất quan trọng vì nó ảnh hưởng trực tiếp đến trải nghiệm, sức khoẻ của bạn trong suốt chuyến du lịch. Thường du khách sẽ lựa chọn dựa trên các tiêu chí như sau:</p>
        <ul class="listStyle">
            <li>Loại tàu, đời tàu và thiết kế của tàu</li>
            <li>Trong quá trình tàu vận hành có ổn không? Tàu có say sóng không?</li>
            <li>Chỗ ngồi có rộng rãi, thoáng mát và dễ chịu?</li>
            <li>Giá vé của tàu và dịch vụ đi kèm trên tàu</li>
            <li>Sự phục vụ của nhân viên từ Đặt vé đến khi Đi tàu</li>
            <li>Và trải nghiệm, đánh giá của khách hàng khác trước đó</li>
        </ul>
        <p>Bên dưới là các <strong>hãng tàu cao tốc {{ $keyWord ?? 'tàu cao tốc' }}</strong> tốt nhất với đầy đủ thông tin để Quý khách có thể cân nhắc cho chuyến đi của mình.</p>
        <div class="shipPartnerBox">
            @foreach($item->partners as $partner)
                <div class="shipPartnerBox_item">
                    <a href="/{{ $partner->infoPartner->seo->slug_full }}" class="shipPartnerBox_item_image">
                        <img src="{{ $partner->infoPartner->company_logo }}" alt="{{ $partner->infoPartner->name }}" title="{{ $partner->infoPartner->name }}" />
                    </a>
                    <div class="shipPartnerBox_item_content">
                        <a href="/{{ $partner->infoPartner->seo->slug_full }}"><h3>{{ $partner->infoPartner->name }}</h3></a>
                        <div class="shipPartnerBox_item_content_desc maxLine_4">{{ $partner->infoPartner->seo->seo_description }}</div>
                    </div>
                </div>  
            @endforeach
        </div>
    </div>
</div>