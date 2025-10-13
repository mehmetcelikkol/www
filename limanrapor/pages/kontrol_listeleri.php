<?php
// filepath: c:\wamp64\www\limanrapor\pages\kontrol_listeleri.php
?>
<div class="data-section">
    <div class="data-header">
        <h3>📝 Kontrol Listeleri</h3>
    </div>
    <!-- Genel açıklama tüm tablolardan önce -->
    <div style="background: #f8fafc; font-style: italic; color: #4a5568; margin-bottom:1.5rem; border-radius:8px; padding:1rem;">
        <strong>Açıklama:</strong> <br>
        <span style="color:#c53030;">Kırmızı satırlar</span> <b>manuel</b> olarak operatör tarafından yapılacaktır.<br>
        <span style="color:#3182ce;">Mavi satırlar</span> <b>otomatik</b> olarak SCADA üzerinden gerçekleşir, operatör sadece kontrol etmelidir.<br>
        <span style="color:#f59e0b;">Not:</span> Manuel işlemler tamamlanmadan SCADA adımlarına geçmeyin!
    </div>
    <div class="table-container" style="margin-bottom:2rem;">
        <table class="data-table">
            <thead>
                <tr>
                    <th colspan="4">Rıhtım 7 Kontrol Listesi</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="4" style="background: #f8fafc;">
                        Rıhtım 7'ye gemi yanaştı ve tanklardan birine MEG alınacak kontrol edilmesi gereken yerler
                    </td>
                </tr>
                <tr>
                    <th>#</th>
                    <th>Onay</th>
                    <th>Kontrol Edilecek Yer</th>
                    <th>Tip</th>
                </tr>
                <tr style="background:#fee;">
                    <td>1</td>
                    <td><input type="checkbox" /></td>
                    <td>Vana 3 Açılacak.</td>
                    <td style="color:#c53030;font-weight:bold;">Manuel</td>
                </tr>
                <tr style="background:#fee;">
                    <td>2</td>
                    <td><input type="checkbox" /></td>
                    <td>Vana 5 Açılacak.</td>
                    <td style="color:#c53030;font-weight:bold;">Manuel</td>
                </tr>
                <tr style="background:#fee;">
                    <td>4</td>
                    <td><input type="checkbox" /></td>
                    <td>Vana 7 Açılacak.</td>
                    <td style="color:#c53030;font-weight:bold;">Manuel</td>
                </tr>
<tr style="background:#fffbe6; border-left: 5px solid #f59e0b;">
    <td>5</td>
    <td><input type="checkbox" /></td>
    <td>
        <span style="font-weight:bold; color:#f59e0b;">🛢️ Tank 1 için Vana 8 Açılacak.</span>
    </td>
    <td style="color:#c53030;font-weight:bold;">Manuel</td>
</tr>
<tr style="background:#fffbe6; border-left: 5px solid #f59e0b;">
    <td>6</td>
    <td><input type="checkbox" /></td>
    <td>
        <span style="font-weight:bold; color:#f59e0b;">🛢️ Tank 2 için Vana 11 Açılacak.</span>
    </td>
    <td style="color:#c53030;font-weight:bold;">Manuel</td>
</tr>
                <tr style="background:#ebf8ff;">
                    <td>7</td>
                    <td><input type="checkbox" /></td>
                    <td>Scada üzerinde Rıhtım 7 Tarafından ilgili Tank seçilecek.</td>
                    <td style="color:#3182ce;font-weight:bold;">Otomatik (SCADA)</td>
                </tr>
                <tr style="background:#ebf8ff;">
                    <td>8</td>
                    <td><input type="checkbox" /></td>
                    <td>Pnömatik Valf 1 Otomatik olarak açılacak.</td>
                    <td style="color:#3182ce;font-weight:bold;">Otomatik (SCADA)</td>
                </tr>
            </tbody>
        </table>
    </div>
    <!-- Diğer tablolar aynı şekilde, açıklama satırı eklenmiş -->
    <div class="table-container" style="margin-bottom:2rem;">
        <table class="data-table">
            <thead>
                <tr><th colspan="4">Rıhtım 8 Kontrol Listesi</th></tr>
            </thead>
            <tbody>
                <tr>
                   <td colspan="4" style="background: #f8fafc;">
                        Rıhtım 8'e gemi yanaştı ve tanklardan birine MEG alınacak kontrol edilmesi gereken yerler
                    </td>
                </tr>
                <tr>
                    <th>#</th>
                    <th>Onay</th>
                    <th>Kontrol Edilecek Yer</th>
                    <th>Tip</th>
                </tr>
                <tr style="background:#fee;">
                    <td>1</td>
                    <td><input type="checkbox" /></td>
                    <td>Vana 4 Açılacak.</td>
                    <td style="color:#c53030;font-weight:bold;">Manuel</td>
                </tr>
                <tr style="background:#fee;">
                    <td>2</td>
                    <td><input type="checkbox" /></td>
                    <td>Vana 6 Açılacak.</td>
                    <td style="color:#c53030;font-weight:bold;">Manuel</td>
                </tr>
                <tr style="background:#fee;">
                    <td>4</td>
                    <td><input type="checkbox" /></td>
                    <td>Vana 7 Açılacak.</td>
                    <td style="color:#c53030;font-weight:bold;">Manuel</td>
                </tr>
                <tr style="background:#fffbe6; border-left: 5px solid #f59e0b;">
    <td>5</td>
    <td><input type="checkbox" /></td>
    <td>
        <span style="font-weight:bold; color:#f59e0b;">🛢️ Tank 1 için Vana 8 Açılacak.</span>
    </td>
    <td style="color:#c53030;font-weight:bold;">Manuel</td>
</tr>
<tr style="background:#fffbe6; border-left: 5px solid #f59e0b;">
    <td>6</td>
    <td><input type="checkbox" /></td>
    <td>
        <span style="font-weight:bold; color:#f59e0b;">🛢️ Tank 2 için Vana 11 Açılacak.</span>
    </td>
    <td style="color:#c53030;font-weight:bold;">Manuel</td>
</tr>
                <tr style="background:#ebf8ff;">
                    <td>7</td>
                    <td><input type="checkbox" /></td>
                    <td>Scada üzerinde Rıhtım 8 Tarafından ilgili Tank seçilecek.</td>
                    <td style="color:#3182ce;font-weight:bold;">Otomatik (SCADA)</td>
                </tr>
                <tr style="background:#ebf8ff;">
                    <td>8</td>
                    <td><input type="checkbox" /></td>
                    <td>Pnömatik Valf 2 Otomatik olarak açılacak.</td>
                    <td style="color:#3182ce;font-weight:bold;">Otomatik (SCADA)</td>
                </tr>
            </tbody>
        </table>
        <div class="alert-warning" style="margin: 1rem 0 2rem 0; padding: 1rem; border-radius: 8px; font-size: 1.05em;">
    <strong>Önemli Ortak Bilgi:</strong>
    <ul style="margin-top: 0.5rem; margin-bottom: 0; padding-left: 1.5rem;">
        <li>
            <span style="color:#c53030; font-weight:bold;">Her iki Rıhtımdan da MEG alınırken <u>Vana 7 açık</u> olmak zorundadır.</span>
        </li>
        <li>
            <span style="color:#c53030; font-weight:bold;">Her iki Rıhtımdan da MEG alınırken <u>Vana 1</u> ve <u>Vana 2 kapalı</u> olmalıdır.</span>
        </li>
    </ul>
    <span style="color:#4a5568; font-size:0.95em;">(Bu kurallar hem Rıhtım 7 hem Rıhtım 8 operasyonları için geçerlidir.)</span>
</div>
<!--   
</div>
    
    <div class="table-container" style="margin-bottom:2rem;">
        <table class="data-table">
            <thead>
                <tr><th colspan="2">Tank 1 Kontrol Listesi</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="2" style="background: #f8fafc;">
                        Tank 1 için kontrol edilmesi gereken yerler
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="table-container" style="margin-bottom:2rem;">
        <table class="data-table">
            <thead>
                <tr><th colspan="2">Tank 2 Kontrol Listesi</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="2" style="background: #f8fafc;">
                        Tank 2 için kontrol edilmesi gereken yerler
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
-->
    <div class="table-container" style="margin-bottom:2rem;">
        <table class="data-table">
            <thead>
                <tr><th colspan="4">Tır Çıkış Kontrol Listesi</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="4" style="background: #f8fafc;">
                        Platforma Tır yanaştı ve tanklardan birinden MEG alınacak. Kontrol edilmesi gereken yerler
                    </td>
                </tr>
                <tr>
                    <th>#</th>
                    <th>Onay</th>
                    <th>Kontrol Edilecek Yer</th>
                    <th>Tip</th>
                </tr>
                <tr style="background:#fffbe6; border-left: 5px solid #f59e0b;">
                    <td>1</td>
                    <td><input type="checkbox" /></td>
                    <td><span style="font-weight:bold; color:#f59e0b;">🛢️ Tank 1 için Vana 9 Açılacak.</span></td>
                    <td style="color:#c53030;font-weight:bold;">Manuel</td>
                </tr>
                <tr style="background:#fffbe6; border-left: 5px solid #f59e0b;">
                    <td>2</td>
                    <td><input type="checkbox" /></td>
                    <td><span style="font-weight:bold; color:#f59e0b;">🛢️ Tank 2 için Vana 12 Açılacak.</span></td>
                    <td style="color:#c53030;font-weight:bold;">Manuel</td>
                </tr>
                <tr style="background:#fee;">
                    <td>3</td>
                    <td><input type="checkbox" /></td>
                    <td>Vana 15 açılacak. (Kollektör Emiş)</td>
                    <td style="color:#c53030;font-weight:bold;">Manuel</td>
                </tr>
                <tr style="background:#fee;">
                    <td>4</td>
                    <td><input type="checkbox" /></td>
                    <td>Pompa 1 için Pompa 1 vanaları açılacak.</td>
                    <td style="color:#c53030;font-weight:bold;">Manuel</td>
                </tr>
                <tr style="background:#fee;">
                    <td>5</td>
                    <td><input type="checkbox" /></td>
                    <td>Pompa 2 için Pompa 2 vanaları açılacak.</td>
                    <td style="color:#c53030;font-weight:bold;">Manuel</td>
                </tr>
                <tr style="background:#fee;">
                    <td>6</td>
                    <td><input type="checkbox" /></td>
                    <td>Pompa 3 için Pompa 3 vanaları açılacak.</td>
                    <td style="color:#c53030;font-weight:bold;">Manuel</td>
                </tr>
                <tr style="background:#fee;">
                    <td>7</td>
                    <td><input type="checkbox" /></td>
                    <td>MEG1 için M1 Vanası açılacak.</td>
                    <td style="color:#c53030;font-weight:bold;">Manuel</td>
                </tr>
                <tr style="background:#fee;">
                    <td>8</td>
                    <td><input type="checkbox" /></td>
                    <td>MEG2 için M2 Vanası açılacak.</td>
                    <td style="color:#c53030;font-weight:bold;">Manuel</td>
                </tr>
                <tr style="background:#fee;">
                    <td>9</td>
                    <td><input type="checkbox" /></td>
                    <td>MEG3 için M3 Vanası açılacak.</td>
                    <td style="color:#c53030;font-weight:bold;">Manuel</td>
                </tr>
                <!-- Tır Çıkış Kontrol Listesi tablosunun devamı -->
<tr>
    <td>10</td>
    <td><input type="checkbox" /></td>
    <td>
        <strong>Nereden kutusu:</strong><br>
        <a href="#" onclick="showModal('img/nereden_kutusu.png'); return false;" style="font-size:0.95em; text-decoration:underline; color:#3182ce; cursor:pointer;">
            Hangi tanktan MEG alacağınızı seçin ve OK tuşuna basın. (SCADA)
        </a>
    </td>
    <td style="color:#3182ce;font-weight:bold;">Otomatik (SCADA)</td>
</tr>
<tr style="background:#ebf8ff;">
    <td>11</td>
    <td><input type="checkbox" /></td>
    <td>Tank 1 seçilirse Valf 3 otomatik olarak açılacak.</td>
    <td style="color:#3182ce;font-weight:bold;">Otomatik (SCADA)</td>
</tr>
<tr style="background:#ebf8ff;">
    <td>12</td>
    <td><input type="checkbox" /></td>
    <td>Tank 2 seçilirse Valf 4 otomatik olarak açılacak.</td>
    <td style="color:#3182ce;font-weight:bold;">Otomatik (SCADA)</td>
</tr>
<tr>
    <td>13</td>
    <td><input type="checkbox" /></td>
    <td>Operasyonda Pompa 1 kullanmak için Pompa 1 onay kutusunu aktif yapın.</td>
    <td style="color:#c53030;font-weight:bold;">Manuel</td>
</tr>
<tr>
    <td>14</td>
    <td><input type="checkbox" /></td>
    <td>Operasyonda Pompa 2 kullanmak için Pompa 2 onay kutusunu aktif yapın.</td>
    <td style="color:#c53030;font-weight:bold;">Manuel</td>
</tr>
<tr>
    <td>15</td>
    <td><input type="checkbox" /></td>
    <td>Operasyonda Pompa 3 kullanmak için Pompa 3 onay kutusunu aktif yapın.</td>
    <td style="color:#c53030;font-weight:bold;">Manuel</td>
</tr>
<tr>
    <td>16</td>
    <td><input type="checkbox" /></td>
    <td>
        Platformda <strong>MEG 1</strong>'e Tır dolumu yapılacaksa SCADA üzerindeki <span style="color:#3182ce;">MEG 1 Tır</span> görseline 
        <a href="#" onclick="showModal('img/tanker-2.png'); return false;" style="text-decoration:underline; color:#3182ce;">tıklayın</a>.
    </td>
    <td style="color:#3182ce;font-weight:bold;">Otomatik (SCADA)</td>
</tr>
<tr>
    <td>17</td>
    <td><input type="checkbox" /></td>
    <td>
        Açılan pencerede 
        <a href="#" onclick="showModal('img/plakagir.png'); return false;" style="text-decoration:underline; color:#3182ce;">plaka ve MEG miktarı</a> girin (ton olarak).
    </td>
    <td style="color:#3182ce;font-weight:bold;">Otomatik (SCADA)</td>
</tr>
<tr>
    <td>18</td>
    <td><input type="checkbox" /></td>
    <td>
        <strong>Başla</strong> butonuna bastığınızda dolum için gereken pnömatik valf açılacaktır.
    </td>
    <td style="color:#3182ce;font-weight:bold;">Otomatik (SCADA)</td>
</tr>
<tr>
    <td>19</td>
    <td><input type="checkbox" /></td>
    <td>
        Platformda <strong>MEG 2</strong>'ye Tır dolumu yapılacaksa SCADA üzerindeki <span style="color:#3182ce;">MEG 2 Tır</span> görseline 
        <a href="#" onclick="showModal('img/tanker-2.png'); return false;" style="text-decoration:underline; color:#3182ce;">tıklayın</a>.
    </td>
    <td style="color:#3182ce;font-weight:bold;">Otomatik (SCADA)</td>
</tr>
<tr>
    <td>20</td>
    <td><input type="checkbox" /></td>
    <td>
        Açılan pencerede 
        <a href="#" onclick="showModal('img/plakagir.png'); return false;" style="text-decoration:underline; color:#3182ce;">plaka ve MEG miktarı</a> girin (ton olarak).
    </td>
    <td style="color:#3182ce;font-weight:bold;">Otomatik (SCADA)</td>
</tr>
<tr>
    <td>21</td>
    <td><input type="checkbox" /></td>
    <td>
        <strong>Başla</strong> butonuna bastığınızda dolum için gereken pnömatik valf açılacaktır.
    </td>
    <td style="color:#3182ce;font-weight:bold;">Otomatik (SCADA)</td>
</tr>
<tr>
    <td>22</td>
    <td><input type="checkbox" /></td>
    <td>
        Platformda <strong>MEG 3</strong>'e Tır dolumu yapılacaksa SCADA üzerindeki <span style="color:#3182ce;">MEG 3 Tır</span> görseline 
        <a href="#" onclick="showModal('img/tanker-2.png'); return false;" style="text-decoration:underline; color:#3182ce;">tıklayın</a>.
    </td>
    <td style="color:#3182ce;font-weight:bold;">Otomatik (SCADA)</td>
</tr>
<tr>
    <td>23</td>
    <td><input type="checkbox" /></td>
    <td>
        Açılan pencerede 
        <a href="#" onclick="showModal('img/plakagir.png'); return false;" style="text-decoration:underline; color:#3182ce;">plaka ve MEG miktarı</a> girin (ton olarak).
    </td>
    <td style="color:#3182ce;font-weight:bold;">Otomatik (SCADA)</td>
</tr>
<tr>
    <td>24</td>
    <td><input type="checkbox" /></td>
    <td>
        <strong>Başla</strong> butonuna bastığınızda dolum için gereken pnömatik valf açılacaktır.
    </td>
    <td style="color:#3182ce;font-weight:bold;">Otomatik (SCADA)</td>
</tr>
            </tbody>
        </table>
    </div>
    <div class="table-container" style="margin-bottom:2rem;">
        <table class="data-table">
            <thead>
                <tr><th colspan="4">Tanktan Tanka Kontrol Listesi</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="4" style="background: #f8fafc;">
                        Tanktan tanka transfer için kontrol edilmesi gereken yerler
                    </td>
                </tr>
                <tr>
                    <th>#</th>
                    <th>Onay</th>
                    <th>Kontrol Edilecek Yer</th>
                    <th>Tip</th>
                </tr>
                
    <tr style="background:#fffbe6; border-left: 5px solid #f59e0b;">
        <td>1</td>
        <td><input type="checkbox" /></td>
        <td>
            <span style="font-weight:bold; color:#f59e0b;">🛢️ Tank 1'den MEG alınacaksa Vana 9 ve Vana 13 Açılacak.</span>
        </td>
        <td style="color:#c53030;font-weight:bold;">Manuel</td>
    </tr>
    <tr style="background:#fffbe6; border-left: 5px solid #f59e0b;">
        <td>2</td>
        <td><input type="checkbox" /></td>
        <td>
            <span style="font-weight:bold; color:#f59e0b;">🛢️ Tank 2'den MEG alınacaksa Vana 10 ve Vana 12 Açılacak.</span>
        </td>
        <td style="color:#c53030;font-weight:bold;">Manuel</td>
    </tr>
    <tr>
        <td>3</td>
        <td><input type="checkbox" /></td>
        <td>
            Hangi tanktan MEG alınacağını seçin ve OK butonuna basın.<br>
            <a href="#" onclick="showModal('img/nereden_kutusu.png'); return false;" style="text-decoration:underline; color:#3182ce; cursor:pointer;">
                Nereden kutusu (SCADA)
            </a>
        </td>
        <td style="color:#3182ce;font-weight:bold;">Otomatik (SCADA)</td>
    </tr>
    <tr>
        <td>4</td>
        <td><input type="checkbox" /></td>
        <td>
            Nereye MEG verileceğini seçin ve ok butonuna basın.<br>
            <a href="#" onclick="showModal('img/nereye_kutusu.png'); return false;" style="text-decoration:underline; color:#3182ce; cursor:pointer;">
                Nereye kutusu (SCADA)
            </a>
        </td>
        <td style="color:#3182ce;font-weight:bold;">Otomatik (SCADA)</td>
    </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal HTML (sayfanın en altına ekleyin, bir kez yeterli) -->
<div id="imgModal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.4); z-index:9999; align-items:center; justify-content:center;">
    <div style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 16px #0002; position:relative; max-width:400px; margin:auto;">
        <img id="modalImg" src="" alt="Popup Görsel" style="max-width:100%; border-radius:4px; border:1px solid #ddd;">
        <button onclick="document.getElementById('imgModal').style.display='none'" style="position:absolute; top:8px; right:8px; background:#c53030; color:#fff; border:none; border-radius:4px; padding:4px 10px; cursor:pointer;">Kapat</button>
    </div>
</div>

<script>
function showModal(imgPath) {
    document.getElementById('modalImg').src = imgPath;
    document.getElementById('imgModal').style.display = 'flex';
}
</script>

<style>
.hover-image:hover .popup-image {
    display: block;
}
</style>