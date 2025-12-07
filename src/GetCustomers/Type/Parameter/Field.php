<?php declare(strict_types=1);

namespace DeRidderDenHertog\GetCustomers\Type\Parameter;

enum Field: string
{
    case TblKlantenID = 'TblKlantenID';
    case Klantnummer = 'Klantnummer';
    case Naam1 = 'Naam1';
    case Naam2 = 'Naam2';
    case Naam3 = 'Naam3';
    case Adres = 'Adres';
    case PC = 'PC';
    case Plaats = 'Plaats';
    case IsWerknemer = 'IsWerknemer';
    case FactuurBoekenOpWinkel = 'FactuurBoekenOpWinkel';
    case DebiteurnrBoekhouding = 'DebiteurnrBoekhouding';
    case EmailFacturatie = 'EmailFacturatie';
    case Korting = 'Korting';
    case Btwnummer = 'Btwnummer';
    case Soort = 'Soort';
    case Prijslijn = 'Prijslijn';
    case PakbonAantalKopieen = 'PakbonAantalKopieen';
    case PakbonZonderPrijzen = 'PakbonZonderPrijzen';
    case PakbonStreepHandtekening = 'PakbonStreepHandtekening';
    case LaatsteWijziging = 'LaatsteWijziging';
    case TblMettlerKortingenKode = 'TblMettlerKortingenKode';
    case LandCodeISO2 = 'LandCodeISO2';
    case Betaalmethode = 'Betaalmethode';
    case Telefoonnr = 'Telefoonnr';
    case Opmerkingen = 'Opmerkingen';
    case Routenr = 'Routenr';
    case FrequentieFactureren = 'FrequentieFactureren';
    case Emballagefactureren = 'Emballagefactureren';
    case EmailPakbonnen = 'EmailPakbonnen';
    case TblKlantGrpID = 'TblKlantGrpID';
    case Kaartnr = 'Kaartnr';
    case Geboortedatum = 'Geboortedatum';
    case StandaardFactuurTekst = 'StandaardFactuurTekst';
    case BezorgAdres = 'BezorgAdres';
    case BezorgPC = 'BezorgPC';
    case BezorgPlaats = 'BezorgPlaats';
    case IncassoTekst = 'IncassoTekst';
    case Terattentievanvoordefactuur = 'Terattentievanvoordefactuur';
    case GeenBTWBerekenen = 'GeenBTWBerekenen';
    case MagNietMeerOpRekeningKopen = 'MagNietMeerOpRekeningKopen';
    case Bestellingen1 = 'Bestellingen1';
    case Bestellingen2 = 'Bestellingen2';
    case Bestellingen3 = 'Bestellingen3';
    case Bestellingen4 = 'Bestellingen4';
    case Bestellingen5 = 'Bestellingen5';
    case Bestellingen6 = 'Bestellingen6';
    case Bestellingen7 = 'Bestellingen7';
    case KlantViaKioskNogNaarKassa = 'KlantViaKioskNogNaarKassa';
    case KlantWilOokUBLBestand = 'KlantWilOokUBLBestand';
}
