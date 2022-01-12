#1. Co to jest orm
-----------------
Orm jest biblioteką wspomagającą translację obiektów utworzonych w php, na rekordy relacyjnej bazy danych, i odwrotnie,
tzn. za pomocą tej biblioteki można dokonać translacji rekordów bazy danych na obiekty php. Biblioteka umożliwia także
generowanie zapytań aktualizujących strukturę bazy danych do stanu zastanego w konfiguracji poszczególnych klas (migracje),
a także automatyczne tworzenie obiektów, np. wymaganych wartości słownikowych dla nowych systemów (fixtury).
Na chwilę obecną biblioteka współpracuje jedynie z bazą danych MySQL. Nic nie stoi jednak na przeszkodzie, aby zaimplementować
własną bibliotekę do obsługi dowolnej bazy danych.
Założenie przyświecające mi przy tworzeniu tej biblioteki było takie, by utworzyć szybką, łatwą w użyciu bibliotekę 
wspomagającą podstawowe, najczęściej wykonywane operacje na bazie, wykorzystywaną docelowo w prostych systemach typu cms, 
sklepy, proste systemy b2b czy erp, obsługującej jednocześnie lazy loading w celu zaoszczędzenia zasobów oraz umożliwiającej 
pracę na różnych typach kolekcji (oneToMany, manyToOne, manyToMany).

#2. Jak zainstalować orm
-----------------------
Instalacja biblioteki jest bardzo prosta i można jej dokonać za pomocą Composera. Jeśli znalazłeś ją na githubie, to
powinieneś wskazać, skąd Composer powinien pobrać kod biblioteki. W tym celu do pliku composer.json należy dodać wskazanie
na repozytorium, które Composer powinien przeszukiwać w poszukiwaniu bibliotek:

```yml
"repositories": [
  {
    "type": "vcs",
    "url": "https://github.com/expresscore/orm.git"
  }
]
```

Następnie, w tym samym pliku, należy dodać bibliotekę do sekcji "require":

```yml
"require": {
   "expresscore/orm": "1.0.*",
}
```

Potem wystarczy zaktualizować biblioteki poleceniem:

```
php composer update
```

Jeśli bibliotekę znalazłeś na packagist.org, to wystarczy wykonać polecenie:

```
php composer require expresscore/orm
```

Biblioteka zainstaluje się automatycznie i w obu przypadkach, o ile nie zostało to skonfigurowane inaczej, zostanie
zainstalowana w katalogu "vendor".

Nie zapomnij dołączyć do swojego pliku index.php pliku /vendor/autoload.php, aby automatycznie ładować klasy bibliotek
zainstalowanych Composerem.

#3. Jak skonfigurować orm
------------------------
Aby wykorzystywać bibliotekę orm, należy ją najpierw skonfigurować. Entry-pointem do wszelkich funkcjonalności, wchodzących
w skład biblioteki, jest klasa EntityManager, i to obiekt tej klasy musimy utworzyć, aby móc robić cokolwiek innego.
W tym celu należy utworzyć tablicę, zawierającą następujące klucze:

```$config['dsn'] = 'mysql:host=localhost;port=3306;dbname=orm;charset=utf8;';``` - nazwa źródła danych, które posłuży nam do
współpracy z bazą danych

```$config['user'] = 'root';``` - nazwa użytkownika bazy danych. Pamiętaj, że użytkownika root możemy bezpiecznie używać tylko
na lokalnym komputerze z niewystawionym na zewnątrz serwerem http ani bazą danych.

```$config['password'] = null;``` - hasło użytkownika bazy danych. Pamiętaj, że hasło może być puste tylko na lokalnym komputerze
z niewystawionym na zewnątrz serwerem http ani bazą danych.

```$config['entityConfigurationDir'] = 'tests/config/';``` - ścieżka do katalogu, w którym będziemy przechowywać pliki *.orm.yml,
w których będzie zapisana konfiguracja poszczególnych klas, pozwalająca na współpracę z bazą danych

```$config['migrationDir'] = 'tests/migrations/';``` - ścieżka do katalogu, w którym będą tworzone migracje do aktualizowania
struktury bazy danych. Z tego katalogu będą one również pobierane przy wykonywaniu aktualizacji struktury.

```$config['fixtureDir'] = 'tests/fixtures/';``` - ścieżka do katalogu, w którym będą przechowywane pliki *.yml, zawierające
dane niezbędne do automatycznego utworzenia nowych obiektów w bazie danych, np. danych słownikowych przy instalacji
czystego systemu.

```$config['mode'] = 'prod';``` - tryb pracy biblioteki, może przyjmować wartości "prod" lub "dev". W przypadku ustawienia "prod"
dane, które mogą zostać wykorzystane ponownie (ale nie dane z bazy danych, tylko dane takie jak konfiguracja poszczególnych
klas, klasy proxy, itp), są zapisywane do cache w celu uzyskania szybszego dostępu do nich. W przypadku ustawienia "dev"
wszystkie konfiguracje klas i pozostałe dane są pobierane za każdym razem z odpowiedniego źródła.

Kolejną rzeczą, którą musimy mieć, jest klasa tworząca konkretne zapytania dla konkretnej bazy danych. W przypadku bazy
MySQL jest to klasa dołączona do biblioteki, o nazwie expresscore\orm\MySQLAdapter

Mając konfigurację oraz klasę do bezpośredniej wpółpracy z bazą danych możemy utworzyć obiekt klasy EntityManager:

```php
$mysqlAdapter = new expresscore\orm\MySQLAdapter();
$entityManager = expresscore\orm\EntityManager::create($mysqlAdapter, $config);
```

W tym momencie mamy już obiekt klasy EntityManager, którym możemy tworzyć obiekty repozytorów do prostego pobierania danych
z bazy, obiekt query buildera do pobierania obiektów z bazy opartego o bardziej skomplikowane warunki. Za pomocą tego obiektu
możemy również zapisywać lub usuwać rekordy z bazy danych.

#4. Jak utworzyć encję i jej konfigurację
----------------------------------------

Pierwszym krokiem do utworzenia encji powinna być jej konfiguracja. W tym celu w katalogu, który został w tablicy konfiguracyjnej
zdefiniowany jako "entityConfigurationDir" powinniśmy utworzyć plik o nazwie [nazwaKlasy].orm.yml. W naszym przypadku może
to być na przykład User.orm.yml.
Pierwszym krokiem powinno być określenie klasy danej encji, u nas to będzie klasa User. Powinno to wyglądać następująco:

```yml
entity: test\orm\helpers\User
```

Kolejnym obowiązkowym krokiem jest ustalenie, która klasa repozytorium będzie używana do obsługi tej encji. Obiekt określonej
w tym miejscu klasy będzie odpowiedzialny za pobieranie obiektu klasy, którą konfigurujemy, z bazy danych. O ile nie planujemy
rozszerzać mechanizmu pobierania o nowe funkcje, lub nadpisywać istniejących, to powinna być to domyślna klasa repozytorium,
dostępna w bibliotece:

```yml
repository: expresscore\orm\Repository
```

Ostatnim na tym etapie krokiem jest określenie, jakie pola będą znajdowały się w danej encji. Pola definiujemy w kluczu "fields":

```yml
fields:
```

Definicja pola w minimalnej konfiguracji powinna zawierać typ pola. Typy pól dzielą się na typy proste, zawierające konkretną
wartość, oraz typy pomocnicze, wskazujące, że wartościa jest kolekcja innych obiektów, lub klucz obcy (odnośnik od innej
encji). Typy proste są zbieżne z typami z bazy MySQL, i należą do nich:

```tinyint``` - pole przechowujące liczbę całkowitą od 0 do 255 bez znaku lub od -127 do 127 ze znakiem.<br/>
```smallint``` - pole przechowujące liczbę całkowitą od 0 do 65535 bez znaku lub od -32768 do 32768 ze znakiem.<br/>
```mediumint``` - pole przechowujące liczbę całkowitą od 0 do 16777215 bez znaku lub od -8388608 do 8388608 ze znakiem.<br/>
```int``` - pole przechowujące liczbę całkowitą od 0 do 4294967295 bez znaku lub od -2147483647 do 2147483647 ze znakiem.<br/>
```bigint``` - pole przechowujące liczbę całkowitą od 0 do 2^64 - 1 bez znaku lub od -2^63 do 2^63 - 1 ze znakiem.<br/>
```float``` - liczba zmiennoprzecinkowa, możliwa do zapisania w 4 bajtach<br/>
```double``` - liczba zmiennoprzecinkowa, możliwa do zapisania w 8 bajtach (podwójna precyzja)<br/>
```decimal``` - liczba zmiennoprzecinkowa o określonej precyzji, domyślnie jest to 20 miejsc przed przecinkiem i 6 po przeciku<br/>
```varchar``` - pole przechowuje wartość tekstową o rozmiarze do 255 bajtów<br/>
```char``` - pole przechowuje pojedynczy znak<br/>
```tinytext``` - pole przechowuje wartość tekstową o rozmiarze nie przekraczającym 255 bajtów<br/>
```text``` - pole przechowuje wartość tekstową o rozmiarze nie przekraczającym 65535 bajtów<br/>
```mediumtext``` - pole przechowuje wartość tekstową o rozmiarze nie przekraczającym 16777215 bajtów<br/>
```longtext``` - pole przechowuje wartość tekstową o rozmiarze nie przekraczającym 4294967295 bajtów<br/>
```date``` - pole przechowujące datę<br/>
```datetime``` - pole przechowujące datę i czas<br/>
```boolean``` - pole przechowujące wartość logiczną (true lub false)

Typy pomocnicze zostaną omówione w dalszej części tekstu.
Najważniejszym polem jest oczywiście pole z identyfikatorem danej encji. Pole to nazywamy domyślnie id i nadamy mu typ int.
Oprócz tego musimy oznaczyć, że jest to właśnie identyfikator, oraz opcjonalnie nadać mu atrybut AUTO_INCREMENT, aby wartość
w tym polu zwiększała się automatycznie dla każdego nowego rekordu. Powinno to wyglądać tak:

```yml
fields:
  id:
    type: int
    id: true
    extra: auto_increment
```

Następnie dodamy jeszcze nazwę użytkownika, pole powinno się nazywać "name" i przechowywać krótkie wartości tekstowe:

```yml
fields:
  id:
    type: int
    id: true
    extra: auto_increment
  name:
    type: varchar
```

Pola mogą umożliwiać przechowywanie wartości NULL, ale domyślnie są skonfigurowane tak, aby nie mogły przyjmować tej
wartości. Aby zmienić to ustawienie, powinniśmy ustawić dla pola cechę "nullable" na true:

```yml
fields:
  id:
    type: int
    id: true
    extra: auto_increment
  name:
    type: varchar
    nullable: true
```

Całość naszego pliku konfiguracyjnego User.orm.yml powinna wyglądać następująco (zrezygnujemy z możliwości ustawienia w
polu "name" wartości null, aby była zawsze konieczność ustawienia nazwy użytkownika:

```yml
entity: test\orm\helpers\User
repository: expresscore\orm\Repository
fields:
  id:
    type: int
    id: true
    extra: auto_increment
  name:
    type: varchar
```

Kolejnym krokiem jest utworzenie klasy encji w php. Powinna znajdować się w pliku User.php:

```php
<?php
class User
{
    private ?int $id = null;
    private string $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
```

Z pewnością zauważyłeś, że brakuje tam settera do pola $id - nie jest nam potrzebny, wartość temu polu nadaje biblioteka
po zapisaniu lub odczycie obiektu z bazy.
Gdy już mamy utworzoną zarówno klasę encji, jak i jej konfigurację, możemy uruchomić w konsoli polecenie, które utworzy
nam plik zawierający zapytanie tworzące w bazie danych tabelę dla naszej encji. Polecenie to uruchamia plik orm, znajdujący
się w katalogu /bin, przekazuje do skryptu opcje konfiguracyjne, i nakazuje mu utworzenie migracji. Wartości w nawiasach
kwadratowych należy zastąpić swoimi wartościami:

```
php bin/orm -dsn [dsn] -u [user] -p [password] -cd [entityConfigurationDir] -md [migrationDir] -fd [fixtureDir] -ac expresscore\orm\MySQLAdapter generate migration
 ```

Wartości w nawiasach odpowiadają kluczom konfiguracyjnym, które zostały opisane w punkcie "Jak skonfigurować orm". Natomiast
instrukcja "generate migration" wskazuje skryptowi konieczność utworzenia migracji.

Przykładowe wywołanie:
```
php bin/orm -dsn "mysql:host=localhost;port=3306;dbname=orm;charset=utf8" -u root -p -cd tests/config -md tests/migrations -fd tests/fixtures -ac "expresscore\orm\MySQLAdapter" generate migration
```

Po wydaniu tego polecenia w katalogu skonfigurowanym jako [migrationDir] pojawi się plik MigrationXXXXXXX.php, w którym
będą zapisane zapytania, które należy wykonać na bazie danych. Migrację uruchamiamy instrukcją:

```
php bin/orm -dsn [dsn] -u [user] -p [password] -cd [entityConfigurationDir] -md [migrationDir] -fd [fixtureDir] -ac expresscore\orm\MySQLAdapter migrate
```

Po wykonaniu powyższej komendy w bazie danych zostanie utworzona tabela "user".

#5. Jak zapisać encję do bazy danych
-----------------------------------

To akurat jest bardzo prosta operacja. Po utworzeniu konfiguracji klasy User, oraz samej klasy User, musimy utworzyć obiekt
tej klasy i nadać wartości jego polom, aby móc go zapisać do bazy danych. Kolejnym krokiem jest wskazanie, że obiekt należy
utrwalić w bazie danych, robimy to za pomocą metody persist() obiektu klasy EntityManager. Możemy w ten sposób przekazać
do utrwalenia kilka obiektów, ponieważna tym etapie nie są one jeszcze zapisywane w bazie. Do zapisania w bazie służy
metoda flush() obiektu klasy EntityManager. Po wywołaniu tej metody rozpoczynana jest transakcja z bazą danych, a potem
wykonywane są zapisy poszczególnych obiektów. Gdy wszystko przebiegnie poprawnie, transakcja jest zatwierdzana, a obiekty
są widoczne w bazie danych. Gdy coś pójdzie nie tak przy zapisie dowolnego obiektu, to transakcja jest anulowana, i żaden
z obiektów ni eznajdzie się w bazie danych.
Cały proces zapisu dwóch obiektów klasy User może wyglądać tak (pominąłem tutaj etap tworzenia obiektu klasy EntityManager,
jak go utworzyć można przeczytać w punkcie 3):

```php
$user = new User();
$user->setName('user1');
$entityManager->persist($user);

$user2 = new User();
$user2->setName('user2');
$entityManager->persist($user2);

$entityManager->flush();
```

Po wykonaniu tej operacji w bazie danych, w tabeli user powinny znaleźć się dwa rekordy, które próbowaliśmy zapisać.

#6. Jak odczytać encję z bazy danych
------------------------------------

Na to jest kilka sposobów, w zależności od tego, co tak naprawdę chcemy odczytać z bazy, oraz jaki jest stopień złożoności
warunków, wg. których chcemy pobradane.

a. Pobranie obiektu danej klasy po jego id
Możemy to zrobić na dwa sposoby:
- bezpośrednio za pomocą EntityManagera:
```php
$user = $entityManager->find(User::class, 1);
```

- tworząc obiekt repozytorium właściwy dla danego obiektu:

```php
$repository = $entityManager->createRepository(User::class);
$user = $repository->find(1);
```

Dla metod find*(), zarówno EntityManagera jak i Repozytorium, ostatnim parametrem jest $hydrationMode, który służy nam do
określania, czy z bazy danych zostanie pobrany obiekt php ($hydrationMode powinno przyjąć wtedy wartość HydrationMode::Object,
jest to domyślne ustawienie), lub czy z bazy danych zostanie pobrana tablica ($hydrationMode powinno przyjąć wtedy wartość
HydrationMode::Array, przy takim ustawieniu pomijamy etap translacji rekordu na obiekt, a więc przyspieszamy operację).

Jeśli obiekt nie zostanie odnaleziony, to zostanie zwrócona wartość null.

b. Pobranie obiektu danej klasy po jego innych właściwościach:
Tutaj również istnieją dwa sposoby, albo bezpośrednio za pomocą EntityManagera, albo za pomocą dedykowanego klasie
repozytorum. W dalszej części tekstu ograniczę się do przykładów pobrania za pomocą EntityManagera. Poniższy kod wyszuka
jednego użytkownika na podstawie jego nazwy. Gdyby użytkowników o takiej nazwie było więcej, to zostanie zwrócony pierwszy
z nich:

```php
$user = $entityManager->findOneBy(User::class, ['name' => 'user1']);
```

Możliwe jest też pobranie usera po kilku parametrach, np. po name i id:

```php
$user = $entityManager->findOneBy(User::class, ['name' => 'user1', 'id' => 1]);
```

Możliwe jest również przekazanie sortowania, które zostanie zastosowane przy pobieraniu obiektu. Ma to wpływ na to, który
rekord zostanie napotkany jako pierwszy, a więc na to, który zostanie zwrócony:

```php
$user = $entityManager->findOneBy(User::class, ['name' => 'user1'], ['id' => 'DESC']);
```

W przypadku pobierania metodą findOneBy możliwe jest również wskazanie, czy pobrane z bazy dane mają być przetworzone
do postaci obiektu, czy ma być zwrócony rekord zawierający dane pobrane bezpośrednio z bazy. Jako ostatni parametr metody
findOneBy możemy użyć stałej HydrationMode::Object (domyślna), lub HydrationMode::Array - w tym drugim przypadku zostanie
zwrócona tablica wartości:

```php
$user = $entityManager->findOneBy(User::class, ['name' => 'user1'], [], expresscore\orm\HydrationMode::Array);
```

c. Pobranie wielu obiektów lub rekordów
Do pobierania wielu obiektów danej klasy służy metoda findBy.
Działa ona tak samo, jak metoda findOneBy, z tą różnicą, że w zależności od ostatniego parametru zwraca tablicę obiektów
lub tablicę rekordów spełniających zadane kryteria.

d. Pobieranie ilości rekordów
Aby pobrać ilość rekordów w tabeli na podstawie zadanych warunków, powinniśmy użyć metody count():

```php
$count = $entityManager->count(User::class, []);
```

Metoda ta zwraca zawsze ilość znalezionych elementów.

d. Pobieranie na podstawie złożonych warunków z użyciem QueryBuildera
Jeśli chcemy pobrać obiekty na podstawie bardziej skomplikowanych warunków niż tylko porównanie wartości, które oferują
nam metody find*(), powinniśmy użyć QueryBuildera. Jest to narzędzie posiadające o wiele większe możliwości wskazywania
rekordó do pobrania niż metody find*().

Aby używać QueryBuildera, powinniśmy go najpierw utworzyć. Możemy tego dokonać za pomocą naszego entry pointa, czyli obiektu
klasy EntityManager:

```php
$mysqlAdapter = new expresscore\orm\MySQLAdapter();
$entityManager = expresscore\orm\EntityManager::create($mysqlAdapter, $config);
$queryBuilder = $this->entityManager->createQueryBuilder(User::class, 'u');
```

Pierwszym parametrem metody createQueryBuilder jest nazwa klasy, której obiekty chcemy pobierać, a drugim alias tabeli,
z której pobieramy. Aliasu należy potem używać przy definiowaniu warunków.

Po utworzeniu QueryBuildera otwiera nam się szereg możliwości zróżnicowanego pobierania obiektów:
a. Definiowanie warunków pobierania
Do definiowania warunków pobierania mamy do dyspozycji szereg metod:
- public function addField($fieldName, string $alias = null): QueryBuilder
Jeśli chcemy zdefiniować konkretne pola, które mają zostać pobrane z bazy danych, używamy metody addField():

```php
$queryBuilder->addField('u.id')
```

Możemy też użyć aliasu pola, wartość z wybranego pola zostanie zwrócona pod nazwą zdefiniowaną jako alias:

```php
$queryBuilder->addField('u.id', 'userId')
```

Pamiętać należy o tym, że w przypadku zdefiniowania pól do pobrania przy pobieraniu pełnego obiektu zostaną do niego
wstawione tylko te pola, które dodaliśmy do pobierania, a pozostałe będą miały wartości domyślne zdefiniowane w klasie
obiektu (lub null).

Podobna sytuacja ma miejsce przy definiowaniu aliasów, jeśli wartość pola zostanie zwrócona pod inną nazwą, zdefiniowaną
jako alias, to nie zostanie ona wstawiona do obiektu, ponieważ pole w obiekcie ma inną wartość. Z drugiej strony, może 
to być pomocne w przypadku, kiedy chcemy pod konkretne pole wstawić wartość inną, niż wynika to z budowy rekordu bazy
danych, np. budując podzapytanie zwracane jako konkretne pole.

W przypadku, kiedy chcemy pobrać wszystkie pola rekordu, nie musimy używać metody addField.

- public function addWhere(?QueryCondition $queryCondition): QueryBuilder
Jeśli chcemy dodawać do zapytania warunki, które muszą spełniać wartości w poszczególnych polach rekordu, powinniśmy użyć
metody addWhere, jako jej argument podając obiekt klasy QueryCondition:

```php
$queryCondition = new \expresscore\orm\QueryCondition('u.id > :id', 1);
$queryBuilder->addWhere($queryCondition)
```

Gdy na powyższym queryBuilderze wywołamy metodę getTableResult() (o które mowa dalej), to zostaną zwrócone wszystkie obiekty
o id większym od 1.
Oczywiście możemy również tworzyć obiekt klasy QueryCondition bezpośrednio jako argument metody addWhere:

```php
$queryBuilder->addWhere(\expresscore\orm\QueryCondition('u.id > :id', 1));
```

Efekt pobierania będzie ten sam.

Jeśli chcemy pobrać rekord na podstawie większej ilości parametrów, możemy zrobić to w następujący sposób:

```php
$queryBuilder
    ->addWhere(new \expresscore\orm\QueryCondition('u.id > :id', 1))
    ->addWhere(new \expresscore\orm\QueryCondition('u.name = :name', 'user2'));    
```

W tym przypadku zostanie zwrócony rekord o id większym od 1, gdzie nazwa usera (pole name) kest równa 'user2'.
Możemy również tworzyć warunki obejmujące operatory AND lub OR.

```php
$queryConditionId = new \expresscore\orm\QueryCondition('u.id > :id', 1);
$queryConditionName = new \expresscore\orm\QueryCondition('u.name = :name', 'user2');
$queryConditionName2 = new \expresscore\orm\QueryCondition('u.name = :name', 'user1');

$queryConditionIdAndName = new \expresscore\orm\QueryCondition();
$queryConditionIdAndName->addCondition($queryConditionId);
$queryConditionIdAndName->addCondition($queryConditionName, \expresscore\orm\QueryConditionOperator::And);

$queryCondition = new \expresscore\orm\QueryCondition();
$queryCondition->addCondition($queryConditionIdAndName);
$queryCondition->addCondition($queryConditionName2, \expresscore\orm\QueryConditionOperator::Or);

$queryBuilder->addWhere($queryCondition)        
```

W powyższym przypadku zostaną pobrane rekordy, które mają id większe od 1 i nazwę usera 'user2' lub mają nazwę usera 'user1'.
Przy definiowaniu warunków możemy również użyć klasy QueryConditionComparision, która posiada funkcje budujące porównania:

```php
$queryCondition = new \expresscore\orm\QueryCondition(\expresscore\orm\QueryConditionComparision::gte('u.id', 1));    
```

W powyższym przypadku zostanie pobrany rekord o id większym lub równym 1. Działanie poszczególnych funkcji:

\expresscore\orm\QueryConditionComparision::equals - buduje warunek 'wartość pola równa'
\expresscore\orm\QueryConditionComparision::differs - buduje warunek 'wartość pola różna od'
\expresscore\orm\QueryConditionComparision::gt - buduje warunek 'wartość pola większa od'
\expresscore\orm\QueryConditionComparision::gte - buduje warunek 'wartość pola większa lub równa'
\expresscore\orm\QueryConditionComparision::lt - buduje warunek 'wartość pola mniejsza od'
\expresscore\orm\QueryConditionComparision::lte - buduje warunek 'wartość pola mniejsza lub równa'
\expresscore\orm\QueryConditionComparision::isNull - buduje warunek 'wartość pola równa NULL'
\expresscore\orm\QueryConditionComparision::isNotNull - buduje warunek 'wartość pola różna od NULL'
\expresscore\orm\QueryConditionComparision::in - buduje warunek 'wartość pola zawiera się w tablicy'
\expresscore\orm\QueryConditionComparision::notIn - buduje warunek 'wartość pola nie zawiera się w tablicy'
\expresscore\orm\QueryConditionComparision::contains - buduje warunek 'wartość pola zawiera stringa'

- public function addHaving(?QueryCondition $queryCondition): QueryBuilder
Funkcja działa analogicznie jak addWhere, lecz dotyczy pól wyliczeniowych, np. takich, których wartość pobieramy podzapytaniami.

- public function addGroupBy($fieldName): QueryBuilder
Funkcja grupuje rekordy po przekazanej nazwie pola, czyli zostaną zwrócone rekordy, w których wartość w polu wskazanym
w argumencie funkcji nie występowała w tym polu dla wcześniej pobranych tym samym zapytaniem rekordów.

- public function setSorting(?QuerySorting $sorting): QueryBuilder
Funkcja dodaje sortowanie do zapytania. Jeśli chcemy sortować po id malejąco, to może to wyglądać tak:

```php
$sorting = new \expresscore\orm\QuerySorting('u.id', \expresscore\orm\QuerySorting::DIRECTION_DESC);
$queryBuilder->setSorting($sorting);
```

Podobnie jak w przypadku warunków, możemy użyć konstruktora bezpośrednio w argumencie funkcji:
```php
$queryBuilder->setSorting(new \expresscore\orm\QuerySorting('u.id', \expresscore\orm\QuerySorting::DIRECTION_DESC));
```

Gdybyśmy chcieli posortować po id rosnąco (id coraz większe w kolejnych rekordach), należy użyć stałej 
QuerySorting::DIRECTION_ASC:
```php
$queryBuilder->setSorting(new \expresscore\orm\QuerySorting('u.id', \expresscore\orm\QuerySorting::DIRECTION_ASC));
```

Dostępne jest też sortowanie losowe, w poniższym przypadku każde wykonanie zapytania będzie zwracało rekordy w innej kolejności:
```php
$queryBuilder->setSorting(new \expresscore\orm\QuerySorting('u.id', \expresscore\orm\QuerySorting::DIRECTION_RANDOM));
```

Możemy też posortować rekordy w ustalonej kolejności, wskazując po kolei wartości pola, po którym sortujemy. Wtedy, tworząc
obiekt klasy QuerySorting, musimy przekazać dodatkowe argumenty:
```php
$querySorting = new QuerySorting();
$querySorting->addField('u.id', QuerySorting::DIRECTION_ORDERED, [7, 2, 4], QuerySorting::DIRECTION_ASC);
```

Powyższy warunek oznacza, że pobrane zostaną rekordy posiadające id w kolejności 7, 2, 4 (tablica w trzecim argumencie),
a następnie wszystkie pozostałe spełniające warunki, w kolejności rosnącej (czwarty argument).

- public function setLimit(?int $limit): QueryBuilder
Metoda wskazuje maksymalną ilość rekordów, jaka zostanie pobrana zapytaniem.

- public function setOffset(?int $offset): QueryBuilder
Metoda wskazuje przesunięcie z jakim zostaną pobrane rekordy, czyli ile rekordów z początku wyników pobierania zostanie
pominiętych, i ile jednocześnie zostanie dobranych następnych z końca spełniających warunki

- public function addJoin($repositoryOrTableName, string $alias, QueryCondition $queryCondition): QueryBuilder
Metoda łaczy do zapytania inne tabele, z których również możemy pobierać dane, lub konstruować warunki where dotyczące
pól tej dołączonej tabeli. Najprostsza konstrukcja wygląda tak:

```php
$queryBuilder = $this->entityManager->createQueryBuilder(\test\orm\helpers\Product::class, 'p');
$userRepository = $entityManager->createRepository(User::class);

$conditionPriceJoin = new \expresscore\orm\QueryCondition('p.FK_Usr_createdBy = u.id');
$conditionPriceName = new \expresscore\orm\QueryCondition('u.name = :name', 'user 3');
$joinCondition->addCondition($conditionPriceJoin);
$joinCondition->addCondition($conditionPriceName, \expresscore\orm\QueryConditionOperator::And);

$queryBuilder->addJoin($userRepository, 'u', $joinCondition);
```

Zapytanie utworzone powyższym QueryBuilderem pobierze nam wszystkie produkty, które zostały utworzone przez usera o nazwie
'user 3'.

b. Pobieranie pojedynczego obiektu
Jeśli chcemy pobrać pojedynczy obiekt, powinniśmy użyć metody getSingleResult(). Metoda ta w zależności od przekazanego
parametru (domyślnie jest expresscore\orm\HydrationMode::Object) zwróci obiekt, rekord w postaci tablicy, lub null, 
jeśli obiekt na podstawie żądanych parametrów nie został odnaleziony w bazie danych.

d. Pobieranie tablicy obiektów
Jeśli chcemy pobrać tablicę obiektów, powinniśmy użyć metody getTableResult(). Metoda ta w zależności od przekazanego
parametru (domyślnie jest expresscore\orm\HydrationMode::Object), zwróci tablicę obiektów, tablicę rekordów pobranych
z bazy danych, lub pustą tablicę, jeśli żaden rekord nie został odnaleziony na podstawie zadanych parametrów.

e. Pobieranie ilości rekordów
Jeśli chcemy pobrać ilość rekordów spełniających zadane kryteria, powinniśmy użyć metody getCount(). Metoda ta zwróci ilość
rekordów które spełniają zadane kryteria

f. Pobieranie konkretnej wartości
Jeśli chcemy pobrać konkretną wartość z bazy danych (wartość jednego pola), powinniśmy użyć metody getValue(). Zwraca ona
string z wartością pobraną z bazy danych, lub null, jeśli taka wartość nie została znaleziona.


#7. Jak dodać prostą relację z innym obiektem lub obiektami
----------------------------------------------------------
Jeśli chcemy utworzyć relację obiektu z innymi obiektami, konieczne będzie uzupełnienie pliku konfiguracyjnego *.orm.yml
o nowe pola, w których będziemy przechowywać inny obiekt lub kolekcję obiektów. Załóżmy, że chcemy utworzyć obiekt dokumentu,
powiązany z użytkownikiem, który utworzył fakturę (powiązanie oneToMany, czyli jeden użytkownik do wielu dokumentów), 
z pozycjami, na zasadzie, że wiele pozycji może być przypisanych do jednego dokumentu (manyToOne), oraz dokumentami magazynowymi, 
na zasadzie, że jeden dokument może być powiązany z wieloma dokumentami magazynowymi, a jeden dokument magazynowy może być 
powiązany z wieloma dokumentami (manyToMany).

Pierwsze co powinniśmy zrobić, to utworzyć klasę dokumentu oraz jego pozycji, oraz klasę dokumentu magazynowego:

```php
<?php
namespace test\orm\helpers;

class WarehouseDocument
{
    public ?int $id = null;
    public ?string $number;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): void
    {
        $this->number = $number;
    }
}
```

```php
<?php
namespace test\orm\helpers;

use DateTime;
use expresscore\orm\Collection;
use expresscore\orm\LazyCollection;

class Document
{
    private ?int $id = null;
    private ?string $name = null;
    private User $FK_Usr_createdBy;
    private DateTime $createdAt;
    private Collection|LazyCollection $positions;
    private Collection|LazyCollection $warehouseDocuments;

    public function __construct()
    {
        $this->positions = new Collection(DocumentPosition::class);
        $this->warehouseDocuments = new Collection(WarehouseDocument::class);
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getFK_Usr_createdBy(): User
    {
        return $this->FK_Usr_createdBy;
    }

    public function setFK_Usr_createdBy(User $FK_Usr_createdBy): void
    {
        $this->FK_Usr_createdBy = $FK_Usr_createdBy;
    }

    public function getWarehouseDocuments(): LazyCollection|Collection
    {
        return $this->warehouseDocuments;
    }

    public function getPositions(): LazyCollection|Collection
    {
        return $this->positions;
    }

    public function addPosition(DocumentPosition $documentPositon)
    {
        $this->positions->add($documentPositon);
    }

    public function addWarehouseDocument(WarehouseDocument $warehouseDocument)
    {
        $this->warehouseDocuments->add($warehouseDocument);
    }
}
```

```php
<?php
namespace test\orm\helpers;

class DocumentPosition
{
    private ?int $id = null;
    private ?string $name = null;
    private Document $FK_Doc_document;

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getFK_Doc_document(): Document
    {
        return $this->FK_Doc_document;
    }

    public function setFK_Doc_document(Document $FK_Doc_document): void
    {
        $this->FK_Doc_document = $FK_Doc_document;
    }
}
```

Powinniśmy też utworzyć pliki konfiguracyjne do bazy danych, czyli pliki *.orm.yml, o nazwach odpowiadających klasom:

Plik Document.orm.yml
```yml
entity: test\orm\helpers\Document
repository: expresscore\orm\Repository
fields:
  id:
    type: int
    id: true
    extra: auto_increment
  name:
    type: varchar
  FK_Usr_createdBy:
    type: entity
    entityClass: test\orm\helpers\User
  createdAt:
    type: datetime
  positions:
    type: collection
    entityClass: test\orm\helpers\DocumentPosition
    joiningField: FK_Doc_document
  warehouseDocuments:
    type: collection
    entityClass: test\orm\helpers\WarehouseDocument
    joiningField: FK_Doc_document
    relatedObjectField: FK_WaD_warehouseDocument
    joiningClass: test\orm\helpers\DocumentToWarehouseDocument
```

Plik DocumentPosition.orm.yml
```yml
entity: test\orm\helpers\DocumentPosition
repository: expresscore\orm\Repository
fields:
  id:
    type: int
    id: true
    extra: auto_increment
  name:
    type: varchar
  FK_Doc_document:
    type: entity
    entityClass: test\orm\helpers\Document
```

Plik WarehouseDocument.orm.yml
```yml
entity: test\orm\helpers\WarehouseDocument
repository: expresscore\orm\Repository
fields:
  id:
    type: int
    id: true
    extra: auto_increment
  number:
    type: varchar

```

Jeśli chodzi o plik Document.orm.yml, na wyjaśnienie zasługują trzy pola. Pierwszym z nich jest pole 

```yml
  FK_Usr_createdBy:
    type: entity
    entityClass: test\orm\helpers\User
```
To pole jest typu 'entity'. Ten typ oznacza, że w bazie jest zapisywany identyfikator obiektu klasy, wskazanej w polu
'entityClass'. Przy pobieraniu danych z bazy w polu FK_Usr_createdBy jest tworzyny obiekt odpowiedniej klasy (czyli User).

Kolejnym polem jest pole przechowujące pozycje dokumentu, stanowiące kolekcję typu manyToOne:

```yml
  positions:
    type: collection
    entityClass: test\orm\helpers\DocumentPosition
    joiningField: FK_Doc_document
```

Pole to powinno mieć typ 'collection' oraz pole entityClass wskazujące na klasę obiektów, które w tej kolekcji będą 
przechowywane (czyli DocumentPosition). Ostatnim elementem jest wskazanie w polu joiningField nazwy pola klasy DocumentPosition, 
które przechowuje id obiektu dokumentu, z którym dana pozycja jest powiązana. Natomiast w konstruktorze klasy w php powinno być 
zainicjowane jako obiekt klasy Collection, zawierające obiekty klasy DocumentPosition. Chodzi o ten fragment:

```php
    public function __construct()
    {
        $this->positions = new Collection(DocumentPosition::class);
    }
```

Ostatnim polem jest pole przechowujące dokumenty magazynowe, przy założeniu, że dokument może posiadać wiele przypisanych
dokumentów magazynowych, a dokument magazynowy może być przypisany do wielu dokumentów.

```yml
  warehouseDocuments:
    type: collection
    entityClass: test\orm\helpers\WarehouseDocument
    joiningField: FK_Doc_document
    relatedObjectField: FK_WaD_warehouseDocument
    joiningClass: test\orm\helpers\DocumentToWarehouseDocument
```

Podobnie jak pole 'positions', to pole również jest typu 'collection', i również w polu entityClass określamy klasę obiektów,
które znajdą się w tej kolekcji. Do kolekcji manyToMany konieczne jest wykorzystanie tabeli pośredniczącej, przechowującej 
powiązania dokumentów z dokumentami magazynowymi. Kolejne parametry oznaczają:
joiningField - nazwa pola w tabeli pośredniczącej, przechowujące id dokumentu
relatedObjectField - nazwa pola w tabeli pośredniczącej, przechowujące id dokumentu magazynowego
joiningClass - nazwa klasy służącej do połączenia dokumentu z dokumentem magazynowym

Ostatnim krokiem jest utworzenie klasy wiążącej nasz dokument z dokumentami magazynowymi, oraz pliku konfiguracyjnego dla
biblioteki:

```php
<?php
namespace test\orm\helpers;

class DocumentToWarehouseDocument
{
    public ?int $id = null;
    public Document $FK_Doc_document;
    public WarehouseDocument $FK_WaD_warehouseDocument;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getFK_WaD_warehouseDocument(): WarehouseDocument
    {
        return $this->FK_WaD_warehouseDocument;
    }

    public function setFK_WaD_warehouseDocument(WarehouseDocument $FK_WaD_warehouseDocument): void
    {
        $this->FK_WaD_warehouseDocument = $FK_WaD_warehouseDocument;
    }

    public function getFK_Doc_document(): Document
    {
        return $this->FK_Doc_document;
    }

    public function setFK_Doc_document(Document $FK_Doc_document): void
    {
        $this->FK_Doc_document = $FK_Doc_document;
    }

}
```

Plik DocumentToWarehouseDocument.orm.yml:
```yml
entity: test\orm\helpers\DocumentToWarehouseDocument
repository: expresscore\orm\Repository
fields:
  id:
    type: int
    id: true
    extra: auto_increment
  FK_Doc_document:
    type: entity
    entityClass: test\orm\helpers\Document
  FK_WaD_warehouseDocument:
    type: entity
    entityClass: test\orm\helpers\WarehouseDocument
```

Następnie wywołujemy skrypt ```bin/orm``` z parametrami ```generate migration```, aby utworzyć pliki migracyjne zawierające
zapytania do tworzenia tabel do nowo utworzonych obiektów, a następnie wykonujemy te migracje wywołując ten sam skrypt 
z parametrem ```migrate```.

Następnie możemy już wykorzystywać tą klasę. Załóżmy, że w bazie mamy już dokumenty magazynowe o numerach:
```WADOTEST-13, WADOTEST-24, WADOTEST-35, WADOTEST-46```
Załóżmy też, że w bazie danych znajduje się obiekt klasy User o id równym 1. Tego użytkownika przypiszemy do pola 
FK_Usr_createdBy.
Naszym celem jest pobranie tych dokumentów magazynowych i przypisanie ich do nowo utworzonego dokumentu. Do nowo
utworzonego dokumentu dodamy też kilka pozycji. Potem całość zapiszemy do bazy. Możemy to zrobić w ten sposób:

```php
    $userRepository = $this->entityManager->createRepository(User::class);
    $warehouseDocumentRepository = $this->entityManager->createRepository(WarehouseDocument::class);

    /** @var User $user */
    $user = $userRepository->find(1);

    $warehouseDocumentNumbers = ['WADOTEST-13', 'WADOTEST-24', 'WADOTEST-35', 'WADOTEST-46'];
    $documentPositionNames = ['Position 1', 'Position 2', 'Position 3'];
    $warehouseDocuments = $warehouseDocumentRepository->findBy(['number' => $warehouseDocumentNumbers]);

    $document = new Document();
    $document->setName('Document 1');
    $document->setCreatedAt(new DateTime());
    $document->setFK_Usr_createdBy($user);

    foreach ($documentPositionNames as $positionName) {
        $documentPosition = new DocumentPosition();
        $documentPosition->setName($positionName);
        $document->addPosition($documentPosition);
    }

    foreach ($warehouseDocuments as $warehouseDocument) {
        $document->addWarehouseDocument($warehouseDocument);
    }

    $this->entityManager->persist($document);
    $this->entityManager->flush();
```

W ten sposób w bazie znajdzie się zapisany dokument wraz z przypisanym użytkownikiem, pozycjami, oraz dokumentami
magazynowymi. Można to zobaczyć przeglądając w bazie tabele ```document``` (pole FK_Usr_createdBy powinno przyjąć wartość
równą 1), ```document_position``` (pozycje dokumentu), oraz ```document_to_warehouse_document``` (dokumenty magazynowe
przypisane do dokumentu).

#9. Indeksy i klucze obce
-------------------------
Kolejną możliwością oferowaną przez bibliotekę jest możliwość definiowania indeksów i kluczy. Indeksy i klucze tworzą się
automatycznie dla pól typu entity, natomiast możemy utworzyć indeksy dla dowolnych innych pól. Możemy to zrobić poprzez
dodanie do pliku *.orm.yml klucza ```tableIndexes```:

```yml
tableIndexes:
  -
    fields:
      - sortOrder
```

Powyższy zapis oznacza, że zostanie utworzony indeks o utworzonej automatycznie nazwie na polu ```sortOrder```. Oczywiście
możemy tworzyć również indeksy na większej ilości pól, np.:

```yml
tableIndexes:
  -
    fields:
      - sortOrder
      - anotherField
```

Powyższy zapis oznacza, że indeks o utworzonej automatycznie nazwie zostanie utworzony na dwóch polach - ```sortOrder```
oraz ```anotherField```.

UWAGA!
Wszelkie zmiany w plikach *.orm.yml powinny być także przeniesione do bazy poprzez utworzenie plików migracyjnych
(polecenie generate migration) oraz ich wykonanie (polecenie migrate).

Jeśli natomiast chcemy utworzyć lub zmodyfikować działanie utworzonego automatycznie klucza obcego, to możemy to zrobić
poprzez dodanie do pliku *.orm.yml klucza ```foreignKeys```, np. tak:

```yml
foreignKeys:
  -
    columnName: 'FK_Pro_product'
    referencedColumnName: 'id'
    updateRule: 'CASCADE'
    deleteRule: 'CASCADE'
```

Powyższy zapis oznacza, że na kolumnie ```FK_Pro_product``` zostanie utworzony klucz obcy, ze strategią przy usuwaniu
rekordu ```CASCADE``` (czyli nawiazując do dokumentu, który tworzyliśmy, to jeśli taki klucz obcy zdefiniujemy w klasie
DocumentPosition, to oznacza, że przy usuwaniu dokumentu z bazy zostaną także usunięte jego pozycje). Podobna strategia
zostanie zastosowana przy aktualizacji obiektu. Domyślnymi zasadami przy aktualizacji i usuwaniu rekordu jest ```RESTRICT```,
czyli usunięcie dokumentu wymaga wcześniejszego ręcznego usunięcia jego pozycji.

#10. Eventy
-----------
Eventy pozwalają na wykonanie kodu przy poszczególnych operacjach na bazie danych. Eventy możemy podpiąć do następujących
operacji na bazie:

```preRemove``` - operacja wykonywana przed usunięciem obiektu
```postRemove``` - operacja wykonywana po usunięciu obiektu
```preUpdate``` - operacja wykonywana przed aktualizacją obiektu
```postUpdate``` - operacja wykonywana po aktualizacji obiektu
```postLoad``` - operacja wykonywana po zmapowaniu pobranego z bazy rekordu (czyli pobranie obiektu, nie rekordu, patrz 
argument HydrationMode w funkcjach do pobierania obiektów)
```postCreate``` - operacja wykonywana po utworzeniu obiektu metodą ObjectFactory::create

W konfiguracji encji (plik *.orm.yml) eventy umieszczamy w kluczu ```lifecycle```, nazwę eventu stosujemy jako klucz, a 
jako wartość wstawiamy nazwę metody statycznej, która jest wykonywana w danym evencie:

```yml
lifecycle:
  preRemove: test\orm\helpers\ProductEventService::preRemoveEvent
  postRemove: test\orm\helpers\ProductEventService::postRemoveEvent
  preUpdate: test\orm\helpers\ProductEventService::preUpdateEvent
  postUpdate: test\orm\helpers\ProductEventService::postUpdateEvent
  postLoad: test\orm\helpers\ProductEventService::postLoadEvent
  postCreate: test\orm\helpers\ProductEventService::postCreateEvent
```

Poszczególne metody dla eventów są wywoływane z argumentem, którym jest obiekt, którego dotyczy dane zdarzenie, np:

```php
    public static function postLoadEvent(Product $product)
    {
        
    }
```

Więcej szczegółów dotyczących budowy plików *.orm.yml oraz obsługi obiektów można uzyskać analizując testy jednostkowe
dołączone do biblioteki (/tests/Test.php).

#11. Bezpośrednie wywołanie zapytań
-----------------------------------
Biblioteka umożliwia bezpośrednie wykonanie samodzielnie utworzonych zapytań na bazie danych. Dotyczy to zarówno zapytań
aktualizujących lub tworzących dane w bazie, jak i zapytań do pobierania danych. Wszystkie te operacje możemy wykonać
za pomocą obiektu klasy DBConnection, który jest elementem EntityManagera. Aby uzyskać utworzony obiekt klasy DBConnection,
możemy wywołać odpowiedni getter EntityManagera (oczywiście obiekt $entityManager musi być utworzony wcześniej):

```php
$dbConnection = $entityManager->getDbConnection();
```

W obiekcie klasy DBConnection dostępne są następujące metody:

```executeQuery($query)``` - metoda wykonująca zapytanie aktualizujące zawartość bazy (czyli INSERT, UPDATE, DELETE)
```getTable($query)``` - metoda pobierająca listę rekordów za pomocą zapytania SELECT
```getSingleRow($query)``` - metoda pobierająca pojedynczy rekord (pierwszy spełniający kryteria) za pomocą zapytania SELECT
```getValue($query)``` - metoda pobierająca pojedynczą wartość (pierwszą spełniającą kryteria) za pomocą zapytania SELECT

W praktyce wygląda to na przykład tak:

```php
$dbConnection = $entityManager->getDbConnection();

try {
    $dbConnection->executeQuery(/** @lang */'
            INSERT INTO product 
            SET 
            FK_Usr_createdBy=:FK_Usr_createdBy, 
            createdAt=NOW(), 
            creatorBrowser=:creatorBrowser,
            entityOne=:entityOne,
            sortOrder=:sortOrder, 
            name=:name',
            [
                ['name' => 'FK_Usr_createdBy', 'value' => 1, 'type' => QueryCondition::PARAMETER_TYPE_INT],
                ['name' => 'creatorBrowser', 'value' => 'Firefox123', 'type' => QueryCondition::PARAMETER_TYPE_STRING],
                ['name' => 'entityOne', 'value' => 1, 'type' => QueryCondition::PARAMETER_TYPE_INT],
                ['name' => 'sortOrder', 'value' => 12, 'type' => QueryCondition::PARAMETER_TYPE_INT],
                ['name' => 'name', 'value' => 'Nazwa produktu', 'type' => QueryCondition::PARAMETER_TYPE_STRING],
            ]
        );
} catch (PDOException $e) {
    die('Nie udało się zapisać danych w bazie');
}

try {
    $result = $dbConnection->getSingleRow('SELECT * FROM product ORDER BY id DESC LIMIT 0, 1 ');
    var_dump($result);
} catch (PDOException $e) {
    die('Nie udało się pobrać danych z bazy');
}
```

Powyższy przykład uwzglednia bindowanie parametrów, co ze wzgledów bezpieczeństwa jest konieczne w przypadku, kiedy
dane do zapytania pochodzą z zewnątrz. W przypadku, kiedy sami wstawiamy wartości, możemy pominąć cały argument
dotyczący parametrów zapytania w metodzie ```executeQuery```, wstawiając je bezpośrednio w zapytaniu:

```php
$dbConnection = $entityManager->getDbConnection();

try {
    $dbConnection->executeQuery(/** @lang */'
            INSERT INTO product 
            SET 
            FK_Usr_createdBy=1, 
            createdAt=NOW(), 
            creatorBrowser="Firefox123",
            entityOne=1,
            sortOrder=12, 
            name="Nazwa produktu"'
        );
} catch (PDOException $e) {
    die('Nie udało się zapisać danych w bazie');
}
```

#12. Polecenia konsolowe
Jak dowiedzieliście się w punkcie 4, biblioteka jest wyposażona w skrypt, w którym możemy wykonywać polecenia konsolowe.
Możemy go wywołać w następujący sposób:
```
php bin/orm -dsn [dsn] -u [user] -p [password] -cd [entityConfigurationDir] -md [migrationDir] -fd [fixtureDir] -ac expresscore\orm\MySQLAdapter _CIAG_ _POLECEN_
```
Szczegółowy opis parametrów znajduje się w rozdziale 4, natomiast tutaj zajmiemy się ciągami poleceń. Mogą one przyjmować
następujące wartości:

```generate migration``` - poznane już wcześniej polecenie, pozwala na utworzenie pliku migracji, zawierającym zapytania 
aktualizujące strukturę bazy danych ze struktury zastanej w bazie w momencie wykonania polecenia do struktury zdefiniowanej
w plikach *.orm.yml.
```migrate``` - również poznane wcześniej polecenie, pozwala na wykonanie niewykonanych wcześniej migracji, tym samym
fizycznie aktualizując bazę danych wg zapytań w tych migracjach zawartych.
```import fixtures``` - tworzy obiekty wg zdefiniowanych fixtur, zgromadzonych w katalogu zdefiniowanym w parametrze
wywołania ```-fd```. Fixtury zostaną opisane w następnym rozdziale.

#13 Tworzenie fixtur
Fixtury są to pliki w formacie yml, zawierające dane do uzupełnienia encji, którą tworzymy. Pliki fixtur powinny być 
umieszczone w katalogu, który potem przy tworzeniu EntityManagera lub wywoływania skryptu konsolowego definiujemy jako
```fd```, czyli fixture directory.
Sama struktura pliku powinna wyglądać następująco:

```yml
fixture:
  fixtureOrder: 4
  class: test\orm\helpers\Product
  factoryClass: test\orm\helpers\ProductMigrationFactory
  records:
    -
      id: 1
      FK_Usr_createdBy: 2
      createdAt: '2021-08-01 13:01:23'
      FK_Usr_updatedBy: null
      updatedAt: null
      sortOrder: 1
      creatorBrowser: 'Fajerfoks'
      name: 'Produkt 1'
      archived: false
      weight: 1.23
      entityOne: 1
      entityTwo: 2
      date: '2021-09-03 15:16:00'
    -
      id: 2
      FK_Usr_createdBy: 3
      createdAt: '2021-08-01 13:01:23'
      FK_Usr_updatedBy: null
      updatedAt: null
      sortOrder: 2
      creatorBrowser: 'Fajerfoks'
      name: 'Produkt 2'
      archived: false
      weight: 2.33
      entityOne: 2
      entityTwo: 1
      date: '2021-09-03 15:16:00'
```

Jak widzimy, na początku otwieramy plik kluczem ```fixture:```. Następnie mamy klucz ```fixtureOrder```. Oznacza on kolejność
tworzenia obiektów w danej fixturze. Najpierw są tworzone obiekty z fixtur posiadających wartość pola fixtureOrder równą
1, potem 2, itd., czyli najpierw pobierane są wszystkie fixtury w kolejności rosnąco wg wartości pola ```fixtureOrder```,
a dopiero potem są wykonywane po tej wartości. Pozwala nam to na utworzenie wcześniej wpisów w bazie, które są konieczne
w innych, dalszych migracjach.
Kolejnym polem jest pole ```class```, wskazuje ono na klasę obiektu, który powinien być utworzony z użyciem zapisanych
w pliku fixtury danych.
Następnie mamy pole factoryClass. Definiujemy w nim klasę dziedziczącą po klasie MigrationFactoryAbstract, w której musimy
zdefiniować metodę ```createObject```, która to metoda zajmuje się tłumaczeniem poszczególnych rekordów pobranych z pliku
fixtury na obiekt php. Potem taki obiekt musi być zwracany przez tą metodę, i następnie jest zapisywany w bazie danych.
Na końcu pliku migracji mamy klucz ```records```, który jest tablicą tablic wartości, które powinny przyjmować kolejne 
rekordy. 
Mechanizm fixtur działa tak, że wykonywana jest pętla po poszczególnych rekordach z pola ```records```, potem te dane
są zamieniane za pomocą metody ```createObject``` ze zdefiniowanej w kluczu ```factoryClass``` klasy (tutaj jest to 
```test\orm\helpers\ProductMigrationFactory```), a następnie zwrócony przez tą metodę obiekt jest zapisywany do bazy za 
pomocą EntityManagera.

#14. Testy jednostkowe
Aby móc testować bibliotekę za pomocą PHPUnit, powinieneś dostosować testy do swojej konfiguracji. Odbywa się to przez
edycję pliku ```/tests/bootstrap.php```, w funkcji ```getConfig()``` należy wpisać swój DSN oraz login i hasło do bazy
danych. Pozostałe parametry mogą pozostać domyślne.
