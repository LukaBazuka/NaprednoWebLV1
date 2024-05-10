<?php
// Uključivanje datoteke simple_html_dom.php koja omogućava manipulaciju HTML sadržajem.
include("simple_html_dom.php");

// Definiranje sučelja iRadovi s potrebnim metodama.
interface iRadovi
{
    public function create($data); // Metoda za kreiranje objekta radova na temelju predanih podataka.
    public function save(); // Metoda za spremanje podataka u bazu.
    public function read(); // Metoda za čitanje podataka iz baze.
}

// Definicija klase DiplomskiRadovi koja implementira sučelje iRadovi.
class DiplomskiRadovi implements iRadovi
{
    // Privatni članovi klase koji čuvaju podatke o radu.
    private $naziv;
    private $tekst;
    private $link;
    private $oib;

    // Konstruktor klase koji poziva metodu create ako su predani podaci.
    public function __construct($data = null)
    {
        if ($data !== null) {
            $this->create($data);
        }
    }

    // Metoda za postavljanje podataka rada na temelju predanih podataka.
    public function create($data)
    {
        $this->naziv = $data['naziv_rada'];
        $this->tekst = $data['tekst_rada'];
        $this->link = $data['link_rada'];
        $this->oib = $data['oib_tvrtke'];
    }

    // Metoda za čitanje podataka iz baze i ispis na ekran.
    public function read()
    {
        // Uspostava veze s bazom podataka pozivom privatne metode connectToDatabase().
        $connection = $this->connectToDatabase();

        // SQL upit za dohvaćanje svih podataka iz tablice `diplomski_radovi`.
        $sqlQuery = "SELECT * FROM `diplomski_radovi`";
        $result = mysqli_query($connection, $sqlQuery);

        // Provjera je li rezultat upita vraća podatke.
        if (mysqli_num_rows($result) > 0) {
            // Prolazak kroz rezultate i ispisivanje svakog retka na ekran.
            while ($row = mysqli_fetch_assoc($result)) {
                foreach ($row as $key => $value) {
                    echo "<br> {$key} : {$value}";
                }
            }
        } else {
            // Ispis poruke ako tablica nema podataka.
            echo "Empty table";
        }

        // Zatvaranje veze s bazom podataka.
        mysqli_close($connection);
    }

    // Metoda za spremanje podataka rada u bazu.
    public function save()
    {
        // Uspostava veze s bazom podataka pozivom privatne metode connectToDatabase().
        $connection = $this->connectToDatabase();

        // Dobivanje vrijednosti svojstava objekta za spremanje u bazu.
        $naziv = $this->naziv;
        $tekst = $this->tekst;
        $link = $this->link;
        $oib = $this->oib;

        // SQL upit za unos novog zapisa u tablicu `diplomski_radovi`.
        $sqlQuery = "INSERT INTO `diplomski_radovi` (`naziv_rada`, `tekst_rada`, `link_rada`, `oib_tvrtke`) 
                     VALUES ('$naziv', '$tekst', '$link', '$oib')";

        // Izvršavanje SQL upita.
        if (mysqli_query($connection, $sqlQuery)) {
            // Ako je unos uspješan, ponovno čitanje podataka iz baze i ispis na ekran.
            $this->read();
        }

        // Zatvaranje veze s bazom podataka.
        mysqli_close($connection);
    }

    // Privatna metoda za uspostavu veze s bazom podataka.
    private function connectToDatabase()
    {
        // Postavljanje parametara za vezu s bazom podataka.
        $servername = 'localhost';
        $username = 'root';
        $password = '';
        $dbname = 'radovi';

        // Uspostava veze s bazom podataka.
        $connection = mysqli_connect($servername, $username, $password, $dbname);

        // Provjera uspješnosti veze.
        if (!$connection) {
            // U slučaju neuspješne veze, ispisivanje poruke o grešci i prekid izvršavanja.
            die("Connection failed: " . mysqli_connect_error());
        }

        // Vraćanje uspostavljene veze.
        return $connection;
    }
}

// Definicija broja stranice i URL-a za dohvat završnih radova.
$page_num = 3;
$url = "http://stup.ferit.hr/index.php/zavrsni-radovi/page/$page_num";

// Dohvaćanje HTML sadržaja web stranice.
$html = file_get_html($url);

// Prolazak kroz svaki članak na web stranici.
foreach ($html->find('article') as $article) {
    // Dohvaćanje slike i njenog izvora (OIB).
    $image = $article->find('ul.slides li div img')[0];
    $image_source = $image->src;

    // Dohvaćanje naslova i poveznice članka.
    $link = $article->find('h2.entry-title a')[0];
    $link_url = $link->href;
    $link_name = $link->plaintext;

    // Dohvaćanje HTML sadržaja poveznice.
    $link_html = file_get_html($link_url);
    $htmlContent = "";
    foreach ($link_html->find('.post-content') as $linkText) {
        $htmlContent .= $linkText->plaintext;
    }

    // Priprema podataka za novi rad.
    $oib = preg_replace('/[^0-9]/', '', $image_source);

    $diplomski_rad = array(
        'naziv_rada' => $link_name,
        'tekst_rada' => $htmlContent,
        'link_rada' => $link_url,
        'oib_tvrtke' => $oib
    );

    // Kreiranje i spremanje novog rada koristeći klasu DiplomskiRadovi.
    $novi_rad = new DiplomskiRadovi($diplomski_rad);
    $novi_rad->save();
}
?>
