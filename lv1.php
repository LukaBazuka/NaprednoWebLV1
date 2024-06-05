
<?php
include("simple_html_dom.php");

interface iRadovi
{
    public function create($data);
    public function save();
    public function read();
}

class DiplomskiRadovi implements iRadovi
{
    private $naziv_rada=NULL;
    private $tekst_rada=NULL;
    private $link_rada=NULL;
    private $oib_tvrtke=NULL;
    function __construct($data)
        {
            if($data !== NULL)
                $this->create($data);
        }
    public function create($data)
    {
        $this->naziv_rada = $data['naziv_rada'];
        $this->tekst_rada = $data['tekst_rada'];
        $this->link_rada = $data['link_rada'];
        $this->oib_tvrtke = $data['oib_tvrtke'];
    }
    //Funkcija read koja se spaja na bazu podataka i dohvaća radove iz tablice diplomski_radovi i ispisuje na ekran
    public function read()
    {
        //Parametri za povezivanje na bazu podataka radovi
        $servername='localhost';
        $username= 'root';
        $password= '';
        $dbname='radovi';
        //Povezivanje na bazu
        $connection=mysqli_connect($servername,$username, $password, $dbname);
        //Ako je povezivanje uspjesno
        if($connection)
        {
            //Postavljanje upita
            $sqlQuery="SELECT * from `diplomski_radovi`";
            //Izvršavanje upita
            $result=mysqli_query($connection,$sqlQuery);
            //Ako ima rezultata ispiši za svaki redak njegove vrijednosti
            if(mysqli_num_rows($result)>0)
            {
                while($row=mysqli_fetch_assoc($result))
                {
                    foreach($row as $key=>$value)
                    {
                        echo"<br> {$key} : {$value}";
                    }
                }
            }
            else
            {
                echo"Empty table";
            }
        }
        else
        {
            die("Connection failed". mysqli_connect_error());
        }

        
        mysqli_close($connection);
    }

    public function save()
    {
        $servername='localhost';
        $username= 'root';
        $password= '';
        $dbname='radovi';

        $connection=mysqli_connect($servername,$username, $password, $dbname);
        //Varijable $naziv, $tekst, $link, i $oib se koriste za spremanje vrijednosti članova objekta $this.
        $naziv =$this->naziv_rada;
        $tekst = $this->tekst_rada;
        $link = $this->link_rada;
        $oib = $this->oib_tvrtke;
        //Ovdje  spremamo u tablicu diplomski_radovi
        if($connection)
        {
            $sqlQuery="INSERT INTO `diplomski_radovi` (`naziv_rada`,`tekst_rada`,`link_rada`,`oib_tvrtke`) VALUES('$naziv','$tekst','$link','$oib')";
            if(mysqli_query($connection,$sqlQuery))
            {
                $this->read();
            }
        }
        else
        {
            die("Connection failed". mysqli_connect_error());
        }

        mysqli_close($connection);
    }
}
//Korištenjm cUrl-a pristupamo stranici 
$page_num = 3;

$url = "http://stup.ferit.hr/index.php/zavrsni-radovi/page/$page_num";
//$curl = curl_init($url);

$ip_address = gethostbyname($url);
if(!socket_connect($sock , $ip_address , 80))
{
    $errorcode = socket_last_error();
    $errormsg = socket_strerror($errorcode);
     
    die("Ne možemo se spojiti: [$errorcode] $errormsg \n");
}
 
echo "Spojili smo se \n";
 

$read = file_get_html($url);
//Pronalazimo article i prolaizmo kroz svaki artikl kako bi pronašli elemente img za oib, naziv rada, tekst rada i link rada
foreach ($read->find('article') as $article) 
{
    $image = $article->find('ul.slides li div img')[0]; //Pronalazimo slku
    $image_source = $image->src;//Uzimamo njen source

    $link = $article->find('h2.entry-title a')[0]; //Nalazimo link i istovremeno uzimamo naziv rada
    $html = file_get_html($link->href); //Otvaramo link
    $htmlContent = "";
    foreach ($html->find('.post-content') as $linkText) 
    {
        $htmlContent .= $linkText->plaintext;//Nakon što smo ušli u link uzimamo tekst
    }
    //Sve to stavljamo u polje
    $diplomski_rad = array(
            'naziv_rada' => $link->plaintext,
            'tekst_rada' => $htmlContent,
            'link_rada' => $link->href,
            'oib_tvrtke' => preg_replace('/[^0-9]/', '', $image_source)
        );

    $novi_rad = new DiplomskiRadovi($diplomski_rad);
    $novi_rad->save();
}
?>