import { useEffect, useState, useMemo } from 'react';
import { getStats } from '../../services/apiServices.js';
import Layout from '../../layout/Layout.jsx';
import '../../assets/styles/StatPage.css';

function StatPage() {
  const [stats, setStats] = useState([]);
  const [ordenAscendente, setOrdenAscendente] = useState(false); // false = descendente (mejor a peor winrate)
  const [paginaActual, setPaginaActual] = useState(1);
  const itemsPorPagina = 5;

  useEffect(() => {
    // Log para verificar si la función de carga se ejecuta
    console.log("StatPage: useEffect - Iniciando la carga de estadísticas...");
    getStats()
      .then(res => {
        // Log para ver los datos cargados
        console.log("StatPage: Estadísticas cargadas exitosamente:", res.data);
        setStats(res.data);
      })
      .catch(err => {
        console.error('StatPage: Error cargando estadísticas:', err);
      });
  }, []);

  const statsOrdenadas = useMemo(() => {
    // Log para verificar cuándo se recalcula el array ordenado
    console.log("StatPage: useMemo - Recalculando statsOrdenadas. Orden Ascendente:", ordenAscendente);
    // Aseguramos una copia profunda de stats para que el sort no mute el estado original
    const sortedStats = [...stats].sort((a, b) => {
      let comparison = 0;

      // 1. Primer criterio: Winrate (Mayor a menor)
      if (a.winrate !== b.winrate) {
        comparison = b.winrate - a.winrate;
      } else if (a.total_partidas !== b.total_partidas) {
        // 2. Segundo criterio: Partidas Jugadas (Mayor a menor)
        comparison = b.total_partidas - a.total_partidas;
      } else if (a.partidas_ganadas !== b.partidas_ganadas) {
        // 3. Tercer criterio: Partidas Ganadas (Mayor a menor)
        comparison = b.partidas_ganadas - a.partidas_ganadas;
      } else if (a.partidas_empatadas !== b.partidas_empatadas) {
        // 4. Cuarto criterio: Partidas Empatadas (Mayor a menor)
        comparison = b.partidas_empatadas - a.partidas_empatadas;
      } else {
        // 5. Quinto criterio: Partidas Perdidas (Menor a mayor)
        comparison = a.partidas_perdidas - b.partidas_perdidas;
      }

      // Aplicar la dirección de ordenación final
      return ordenAscendente ? -comparison : comparison;
    });
    // Log para ver el primer elemento del array después de ordenar
    console.log("StatPage: statsOrdenadas recalculado. Primer elemento:", sortedStats[0]);
    return sortedStats;
  }, [stats, ordenAscendente]); // Dependencias: recalcular cuando 'stats' u 'ordenAscendente' cambien

  const totalPaginas = Math.ceil(statsOrdenadas.length / itemsPorPagina);
  const statsPaginadas = statsOrdenadas.slice(
    (paginaActual - 1) * itemsPorPagina,
    paginaActual * itemsPorPagina
  );

  const winrateMasAltoGeneral = useMemo(() => {
    return stats.length > 0 ? Math.max(...stats.map(s => s.winrate)) : 0;
  }, [stats]);


  return (
    <Layout>
      <div className="container d-flex justify-content-center">
        <div className="leaderboard-card w-100" style={{ maxWidth: '900px' }}>
          <div className="leaderboard-header">ESTADISTICAS</div>

          <div className="leaderboard-row header static-header">
            <div>#</div>
            <div>Usuario</div>
            <div>PJ</div>
            <div>G</div>
            <div>E</div>
            <div>P</div>
            <div
                className="sortable-header"
                onClick={() => {
                  console.log("¡Clic en Winrate!"); // Solo esto por ahora
                  setOrdenAscendente(!ordenAscendente);
                  setPaginaActual(1);
                }}
                style={{ cursor: 'pointer' }}
              >
                Winrate <i className={`bi ${ordenAscendente ? 'bi-caret-up-fill' : 'bi-caret-down-fill'}`}></i>
              </div>
          </div>

          {statsPaginadas.map((j, index) => (
            <div
              key={j.usuario}
              className={`leaderboard-row animated-row ${j.winrate === winrateMasAltoGeneral && !ordenAscendente ? 'best' : ''}`}
            >
              <div>{(paginaActual - 1) * itemsPorPagina + index + 1}</div>
              <div>{j.usuario}</div>
              <div>{j.total_partidas}</div>
              <div>{j.partidas_ganadas}</div>
              <div>{j.partidas_empatadas}</div>
              <div>{j.partidas_perdidas}</div>
              <div>{j.winrate}%</div>
            </div>
          ))}

          {Array.from({ length: itemsPorPagina - statsPaginadas.length }).map((_, i) => (
            <div className="leaderboard-row" key={`empty-${i}`}>
              <div></div><div></div><div></div><div></div><div></div><div></div><div></div>
            </div>
          ))}

          <nav className="d-flex justify-content-center pagination">
            <ul className="pagination">
              {Array.from({ length: totalPaginas }).map((_, i) => (
                <li key={i} className={`page-item ${i + 1 === paginaActual ? 'active' : ''}`}>
                  <button className="page-link" onClick={() => setPaginaActual(i + 1)}>
                    {i + 1}
                  </button>
                </li>
              ))}
            </ul>
          </nav>
        </div>
      </div>
    </Layout>
  );
}

export default StatPage;