<?php
// Aktif menü belirleme yardımcısı
// Her sayfanın başında $aktif_sayfa = 'dashboard'; gibi tanımlanır
function aktif_menu(string $sayfa): string {
    global $aktif_sayfa;
    return ($aktif_sayfa ?? '') === $sayfa ? 'active' : '';
}
