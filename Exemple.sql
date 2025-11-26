DROP test1 IF EXISTS test1 ;
CREATE DATABASE test1 DEFAULT CHARSET UTF8MB4;

USE test1;

CREATE TABLE Role(
                     Id INT  AUTO_INCREMENT NOT NULL,
                     Libelle VARCHAR(25) NOT NULL,
                     PRIMARY KEY(Id)
);

CREATE TABLE Civilite(
                         Id INT  AUTO_INCREMENT NOT NULL,
                         Libelle VARCHAR(15) NOT NULL,
                         PRIMARY KEY(Id)
);

CREATE TABLE Personne(
                          Id INT  AUTO_INCREMENT NOT NULL,
                          Nom VARCHAR(40) NOT NULL,
                          Prenom VARCHAR(40) NOT NULL,
                          IdRole INT NOT NULL,
                          IdCivilite INT NOT NULL,
                          PRIMARY KEY(Id),
                          FOREIGN KEY (IdRole) REFERENCES Role(Id) ON DELETE RESTRICT,
                          FOREIGN KEY (IdCivilite) REFERENCES Civilite(Id)  ON DELETE RESTRICT
);

CREATE TABLE Permis(
                                Id INT  AUTO_INCREMENT NOT NULL,
                                Libelle VARCHAR(40) NOT NULL,
                                PRIMARY KEY(Id)
);

CREATE TABLE Appartenir(
                                IdPersonne INT NOT NULL,
                                IdPermis INT NOT NULL,
                                PRIMARY KEY(IdPersonne, IdPermis),
                                FOREIGN KEY(IdPersonne) REFERENCES Personne(Id) ON DELETE CASCADE,
                                FOREIGN KEY(IdPermis) REFERENCES Permis(Id) ON DELETE CASCADE
);
