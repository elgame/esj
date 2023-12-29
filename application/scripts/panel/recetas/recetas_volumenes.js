let recetasVolumenes = {
  "list": {
    "Pileta-Raquis": ['10000', '5000'],
    "Pileta-Bioles": ['10000', '5000'],
    "Rotoplas": ['5000', '2500', '1000'],
    "Biodigestor": ['5000', '2500', '1000'],
    "Boom": ['3000', '2750'],
    "Aspersora": ['2000'],
    "Aguilón": ['1500', '800'],
    "Tambo": ['200'],
    "Garrafa": ['20'],
    "Motobomba": [],
    "Termo": [],
    "Mochila": ['19'],
    "Avioneta": ['500'],
    "Dron": [],
    "Sistema Riego": [],
    "Cama-Humus": [],
  },

  get: function(key){
    return recetasVolumenes.list[key]? recetasVolumenes.list[key]: [];
  }
};
