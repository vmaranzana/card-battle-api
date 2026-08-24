// src/pages/deck/AddMazoPage.jsx
import { useEffect, useState } from 'react';
import Layout from '../../layout/Layout.jsx';
import { getCartas,getUserMazos, addMazo } from '../../services/apiServices.js';
import '../../assets/styles/addMazoPage.css';

function AddMazoPage() {
  const [nombre, setNombre] = useState('');
  const [cartas, setCartas] = useState([]);
  const [seleccionadas, setSeleccionadas] = useState([]);
  const [filtroNombre, setFiltroNombre] = useState('');
  const [filtroAtributo, setFiltroAtributo] = useState('');
  const [error, setError] = useState('');
  const [mazoGuardado, setMazoGuardado] = useState(false);

  // Cargar cartas cuando cambian filtros
  useEffect(() => {
    const filtros = {};
    if (filtroNombre) filtros.nombre = filtroNombre;
    if (filtroAtributo) filtros.atributo = filtroAtributo;

    getCartas(filtros)
      .then(res => setCartas(res.data["lista de cartas por criterios"]))
      .catch(err => {
        const mensaje = err.response?.data?.error || 'Error al obtener cartas.';
        setCartas([]);
        setError(mensaje);
      });
  }, [filtroNombre, filtroAtributo]);

  const token = localStorage.getItem("token");
  const userId = localStorage.getItem("id");

  const alternarCarta = (id) => {
    setSeleccionadas(prev =>
      prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
    );
  };

  const limpiarFiltros = () => {
    setFiltroNombre('');
    setFiltroAtributo('');
  };

  const guardarMazo = async () => {
    setError('');
    setMazoGuardado(false);

    const response = await getUserMazos(userId, token);
    const mazos = response.data["lista de mazos del usuario logueado"] || [];

    if (mazos.length >= 3) {
      setError('Ya tienes el máximo de 3 mazos.');
      return;
    }

    if (nombre.length > 20) {
      setError('El nombre del mazo no puede superar los 20 caracteres.');
      return;
    }
    if (seleccionadas.length !== 5) {
      setError('Debes seleccionar exactamente 5 cartas.');
      return;
    }

    try {
      const res = await addMazo({ nombre, carta_id: seleccionadas},token);
      if (res.data.mazo_id) {
        setMazoGuardado(true);
        setNombre('');
        setSeleccionadas([]);
      }
    } catch (err) {
      const mensaje = err.response?.data?.error || 'Error al guardar el mazo.';
      setError(mensaje);
    }
  };

  return (
    <Layout>
    <div className="mazo-card add-mazo w-100" style={{ maxWidth: '1100px' }}>  
      <div className="container-fluid mt-4">
        <div className="row justify-content-center">
        <div className="col-12 col-lg-58">
        <h2>Nuevo mazo</h2>

        <div className="form-group mb-2">
          <label>Nombre del mazo</label>
          <input
            value={nombre}
            onChange={e => setNombre(e.target.value)}
            maxLength={20}
            className="form-control"
          />
        </div>

        <div className="row my-3 filtros">
          <div className="col-md-4">
            <label>Nombre:</label>
            <input
              type="text"
              value={filtroNombre}
              onChange={e => setFiltroNombre(e.target.value)}
              className="form-control"
            />
          </div>
          <div className="col-md-4">
            <label>Atributo:</label>
            <select
              value={filtroAtributo}
              onChange={e => setFiltroAtributo(e.target.value)}
              className="form-control"
            >
              <option value="">Todos</option>
              <option value="Fuego">Fuego</option>
              <option value="Agua">Agua</option>
              <option value="Tierra">Tierra</option>
              <option value="Normal">Normal</option>
              <option value="Volador">Volador</option>
              <option value="Piedra">Piedra</option>
              <option value="Planta">Planta</option>
            </select>
          </div>
          <div className="col-md-4 d-flex align-items-end">
            <button className="btn btn-secondary" onClick={limpiarFiltros}>
              Limpiar filtros
            </button>
          </div>
        </div>

        <div className="cartas-grid">
          {cartas.map((carta, index) => (
            <div key={index} className="carta-item">
              <input
                type="checkbox"
                checked={seleccionadas.includes(carta.carta_id)} // o carta.id si lo tenés
                onChange={() => alternarCarta(carta.carta_id)}
                className="carta-checkbox"
              />

              <div className="carta-nombre">
                <strong>{carta.nombre_carta}</strong>
              </div>

              <div className="carta-info text-end">
                <div>Atributo: {carta.nombre_atributo}</div>
                <div>{carta.nombre_ataque}: {carta.puntos_ataque}</div>
              </div>
            </div>
          ))}
        </div>

        {error && <div className="alert alert-danger mt-3">{error}</div>}
        {mazoGuardado && <div className="alert alert-success mt-3">¡Mazo guardado con éxito!</div>}

        <button className="btn btn-primary mt-3" onClick={guardarMazo}>
          Guardar Mazo
        </button>
        </div>
  </div>
      </div>
      </div>
    </Layout>
  );
}

export default AddMazoPage;
