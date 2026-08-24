// frontend/src/pages/jugar/JugarPage.jsx
import { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate, useLocation } from 'react-router-dom';
import Layout from '../../layout/Layout'; 
import CardComponent from '../../components/CardComponent'; 
import { realizarJugada, obtenerCartasEnManoUsuario, obtenerCartasEnManoServidor } from '../../services/apiServices'; 
import { getToken, getUserData } from '../../utils/auth'; 
import '../../assets/styles/JugarPage.css'; 

function JugarPage() {
  const { idPartida } = useParams(); 
  const navigate = useNavigate();
  const location = useLocation(); 
  
  // Estados para gestionar la UI del juego
  const [cartasEnManoUsuario, setCartasEnManoUsuario] = useState([]);
  const [cartasServidorEnMano, setCartasServidorEnMano] = useState([]); 
  const [cartaJugadaUsuario, setCartaJugadaUsuario] = useState(null);
  const [cartaJugadaServidor, setCartaJugadaServidor] = useState(null); 
  const [mensajeJugada, setMensajeJugada] = useState(''); 
  const [partidaFinalizada, setPartidaFinalizada] = useState(false);
  const [resultadoPartida, setResultadoPartida] = useState(''); 
  const [error, setError] = useState(''); 
  const [isLoading, setIsLoading] = useState(true); 
  const [hasLoadedInitialData, setHasLoadedInitialData] = useState(false); 

  const userData = getUserData(); 

  // Efecto para cargar el estado inicial de la partida
  useEffect(() => {
    console.log("JugarPage useEffect (carga inicial): Inicio de carga de datos.");
    if (hasLoadedInitialData && !isLoading) {
        console.log("JugarPage useEffect (carga inicial): Datos iniciales ya cargados para la partida actual, evitando re-fetch.");
        return;
    }

    const token = getToken();
    
    if (!token || !userData || !idPartida) {
      console.warn("JugarPage useEffect (carga inicial): Faltan token, userData o idPartida. Redirigiendo a /login.");
      setError('Debes iniciar sesión para jugar o la partida no es válida.');
      setIsLoading(false); 
      setHasLoadedInitialData(true); 
      navigate('/login', { state: { from: location.pathname } }); 
      return;
    }

    const loadGameData = async () => {
      setIsLoading(true); 
      setHasLoadedInitialData(false); 
      console.log("JugarPage loadGameData: Iniciando fetch de datos de partida.");

      // 1. Cargar cartas del USUARIO (priorizando location.state)
      let fetchedUserCards = [];
      if (location.state && Array.isArray(location.state.cartasEnMano) && location.state.idPartida === idPartida) {
        console.log("JugarPage loadGameData: Cargando cartas de usuario desde location.state (Mis Mazos).");
        fetchedUserCards = location.state.cartasEnMano;
        setCartasEnManoUsuario(fetchedUserCards);
      } else {
        try {
          console.log(`JugarPage loadGameData: Intentando obtener cartas de usuario desde la API para usuario ${userData.id}, partida ${idPartida}.`);
          const response = await obtenerCartasEnManoUsuario(userData.id, idPartida, token);
          if (response.data && Array.isArray(response.data.cartas)) {
            console.log("JugarPage loadGameData: Cartas de usuario obtenidas exitosamente desde la API.");
            fetchedUserCards = response.data.cartas;
            setCartasEnManoUsuario(fetchedUserCards); 
          } else {
            console.error("JugarPage loadGameData: Formato de datos de cartas de usuario iniciales inesperado:", response.data);
            setCartasEnManoUsuario([]); 
            setError('Formato de datos de cartas de usuario iniciales inesperado.');
            navigate('/mis-mazos'); 
            return; 
          }
        } catch (err) {
          console.error('JugarPage loadGameData: Error al obtener cartas de usuario iniciales:', err.response?.data || err.message || err);
          setCartasEnManoUsuario([]); 
          setError(err.response?.data?.message || 'Error al cargar cartas de usuario. Revisa la consola.');
          navigate('/mis-mazos'); 
          return; 
        }
      }
      setError(''); 

      // 2. Cargar cartas del SERVIDOR desde la API (siempre desde la API)
      try {
        console.log(`JugarPage loadGameData: Intentando obtener cartas del servidor desde la API para partida ${idPartida}.`);
        const serverResponse = await obtenerCartasEnManoServidor(idPartida, token); 
        if (serverResponse.data && Array.isArray(serverResponse.data.cartas_servidor)) { 
          console.log("JugarPage loadGameData: Cartas del servidor obtenidas exitosamente desde la API.");
          setCartasServidorEnMano(serverResponse.data.cartas_servidor); 
        } else {
          console.error("JugarPage loadGameData: Formato de datos de cartas del servidor inesperado:", serverResponse.data);
          setCartasServidorEnMano([]);
          setError('Formato de datos de cartas del servidor inesperado. Revisa la consola.');
        }
      } catch (err) {
        console.error('JugarPage loadGameData: Error al obtener cartas del servidor de la API:', err.response?.data || err.message || err);
        setCartasServidorEnMano([]);
        setError(err.response?.data?.message || 'Error al cargar cartas del servidor. Revisa la consola.');
      } finally {
        setIsLoading(false); 
        setHasLoadedInitialData(true); 
        console.log("JugarPage loadGameData: Finalizado el fetch de datos de partida. isLoading:", false, "hasLoadedInitialData:", true);
      }
    };

    if (!hasLoadedInitialData) {
        loadGameData();
    }

  }, [
    idPartida, 
    location.state, 
    navigate, 
    userData, 
    hasLoadedInitialData, 
    isLoading 
  ]);

  // Efecto para manejar el intento de abandonar la partida
  useEffect(() => {
    const handleBeforeUnload = (event) => {
      if (!partidaFinalizada && cartasEnManoUsuario.length > 0) { 
        event.preventDefault(); 
        event.returnValue = ''; 
        return '¿Estás seguro de que quieres abandonar la partida? Perderás el progreso actual.';
      }
    };

    window.addEventListener('beforeunload', handleBeforeUnload);

    return () => {
      window.removeEventListener('beforeunload', handleBeforeUnload);
    };
  }, [partidaFinalizada, cartasEnManoUsuario.length]);

  // Función para manejar la jugada de una carta
  const handlePlayCard = useCallback(async (cardId) => {
    console.log(`handlePlayCard: Card ID received: ${cardId}, Type: ${typeof cardId}`);

    if (partidaFinalizada) {
      setError('La partida ya ha finalizado. Por favor, inicia una nueva.');
      return; 
    }

    setError(''); 
    setMensajeJugada('');
    setCartaJugadaServidor(null); // Limpiar la carta jugada del servidor antes de la nueva ronda

    const token = getToken();
    if (!token || !userData) {
      console.warn("handlePlayCard: Token o userData no disponibles. Redirigiendo a /login.");
      navigate('/login', { state: { from: location.pathname } });
      return;
    }

    const cartaJugada = cartasEnManoUsuario.find(c => c.id === cardId);
    if (!cartaJugada) {
        console.warn("handlePlayCard: La carta seleccionada no está en la mano del usuario.");
        setError('La carta seleccionada no está en tu mano.');
        return;
    }
    setCartaJugadaUsuario(cartaJugada); // Setear la carta del usuario con todos sus detalles

    try {
      console.log(`handlePlayCard: Realizando jugada para partida ${idPartida}, carta ${cardId}.`);
      console.log("handlePlayCard: Payload for realizarJugada:", { id_partida: idPartida, carta_jugada: cardId });

      const response = await realizarJugada(idPartida, cardId, token);
      const {
        carta_servidor: serverCardIdFromBackend,
        resultado_jugada: resultadoJugada,
        ganador_juego: isPartidaFinalizada
      } = response.data;

      console.log("handlePlayCard: Jugada exitosa. Respuesta de la API:", response.data);
      console.log("handlePlayCard: ID de carta del servidor devuelta del backend:", serverCardIdFromBackend);

      const fullServerCardDetails = cartasServidorEnMano.find(c => c.id === serverCardIdFromBackend);

      if (fullServerCardDetails) {
        setCartaJugadaServidor(fullServerCardDetails);
        console.log("handlePlayCard: Carta del servidor jugada con detalles completos (encontrados en mano):", fullServerCardDetails);

        // También actualiza la mano del servidor eliminando la carta que acaba de jugar
        setCartasServidorEnMano(prevCartas => {
            const updatedCards = prevCartas.filter(c => c.id !== fullServerCardDetails.id);
            console.log("handlePlayCard: Mano del servidor actualizada, cartas restantes:", updatedCards.length);
            return updatedCards;
        });

      } else {
        console.error("handlePlayCard: No se encontraron detalles completos para la carta del servidor jugada con ID:", serverCardIdFromBackend);
        setCartaJugadaServidor(null);
        setError('No se pudieron obtener los detalles completos de la carta del servidor jugada.');
      }

      setMensajeJugada(resultadoJugada);

      setCartasEnManoUsuario(prevCartas => prevCartas.filter(c => c.id !== cardId));

      if (isPartidaFinalizada !== 'Partida en curso') {
        setPartidaFinalizada(true);
        if (isPartidaFinalizada === 'gano') { 
          setResultadoPartida('GANADA');
        } else if (isPartidaFinalizada === 'perdio'){
          setResultadoPartida('PERDIDA');
        } else {
          setResultadoPartida('EMPATE');
        }
        console.log("handlePlayCard: Partida finalizada. Ganador:", isPartidaFinalizada);
      }

    } catch (err) {
      console.error('handlePlayCard: Error al realizar jugada con la API:', err.response?.data || err.message || err);
      setCartaJugadaUsuario(null);
      setCartaJugadaServidor(null); 
      setError(err.response?.data?.message || 'Error al procesar la jugada. Intenta de nuevo.');
    }
  }, [idPartida, cartasEnManoUsuario, partidaFinalizada, navigate, location.pathname, userData, cartasServidorEnMano]); 


  // Función para el botón "Jugar Otra Vez"
  const handlePlayAgain = () => {
    console.log("JugarPage: Botón 'Jugar Otra Vez' presionado. Redirigiendo a /mis-mazos.");
    navigate('/mis-mazos'); 
  };

  if (isLoading) {
    return (
      <Layout>
        <div className="game-container">
          <h2>Cargando partida...</h2>
          <p>Por favor espera.</p>
        </div>
      </Layout>
    );
  }

  if (error && !partidaFinalizada && !isLoading) { 
    return (
      <Layout>
        <div className="game-container">
          <h2 className="error-message">Error: {error}</h2>
          <button className="btn btn-primary mt-3" onClick={() => navigate('/mis-mazos')}>
            Volver a Mis Mazos
          </button>
        </div>
      </Layout>
    );
  }

  return (
    <Layout>
      <div className="game-container">
        {partidaFinalizada && (
          <div className="game-result-overlay">
            <div className="game-result-modal">
              <h3>¡{resultadoPartida}!</h3>
              <button className="btn m-1" onClick={handlePlayAgain}>JUGAR OTRA VEZ</button>
              
              <button className="btn m-1" onClick={() => window.location.href = "/"} >VOLVER A INICIO</button>
            </div>
          </div>  
        )}

        {/* Zona de cartas del servidor (mano) */}
        <div className="card-zone server-hand-area"> 
          <h3 className="section-title">Cartas del Servidor</h3> 
          <div className="server-cards-placeholder">
            {Array.isArray(cartasServidorEnMano) && cartasServidorEnMano.length > 0 ? (
              cartasServidorEnMano.map((card) => ( 
                <CardComponent 
                  key={card.id} 
                  card={card}
                  isFlipped={true} // Las cartas del servidor en la mano están siempre volteadas
                />
              ))
            ) : (
              <p>No hay cartas del servidor disponibles.</p>
            )}
          </div>
        </div>

        {/* Zona de juego (tablero) */}
        <div className="game-board">
          {cartaJugadaUsuario && cartaJugadaServidor ? (
            <div className="played-cards">
              <div className="player-played-card">
                <h4>Tu Carta</h4>
                <CardComponent card={cartaJugadaUsuario} isFlipped={false} /> {/* Tu carta siempre se muestra de frente */}
              </div>
              <div className="server-played-card">
                <h4>Servidor</h4>
                <CardComponent card={cartaJugadaServidor} isFlipped={false} /> {/* La carta jugada del servidor ahora se muestra de frente */}
              </div>
            </div>
          ) : (
            <p className="waiting-message">Selecciona una carta para jugar...</p>
          )}

          {mensajeJugada && cartaJugadaUsuario && cartaJugadaServidor && (
            <div className="round-result">
              <p>Resultado de la Jugada</p>
              <p><strong>{mensajeJugada}</strong></p>
            </div>
          )}
        </div>

        {/* Zona de cartas del jugador (mano) */}
        <div className="card-zone player-hand-area"> 
          <h3 className="section-title">Tu Mano</h3>
          <div className="player-cards">
            {Array.isArray(cartasEnManoUsuario) && cartasEnManoUsuario.length > 0 ? (
              cartasEnManoUsuario.map((card) => ( 
                <CardComponent
                  key={card.id} 
                  card={card}
                  isFlipped={false} 
                  onPlay={handlePlayCard} 
                />
              ))
            ) : (
              !partidaFinalizada && <p>No tienes más cartas en mano.</p>
            )}
          </div>
        </div>
      </div>
    </Layout>
  );
}

export default JugarPage;