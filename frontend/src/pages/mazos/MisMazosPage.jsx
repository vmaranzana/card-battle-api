import { useEffect, useState } from "react";
import { getUserMazos , getCartasDeMazo, editarNombreMazo, eliminarMazo, iniciarPartida } from '../../services/apiServices.js'; // Importa iniciarPartida
import { Link, useNavigate } from "react-router-dom";
import Layout from '../../layout/Layout.jsx';
import '../../assets/styles/MisMazos.css';
import Modal from "../../components/Modal.jsx";

function MisMazosPage() {
    
    //Variables para controlar el login
    const userId =  localStorage.getItem("id");
    const token = localStorage.getItem("token");

    // Hook de navegación
    const navigate = useNavigate();

    //Estados para controlar el modal
    const [mazoSeleccionado, setMazoSeleccionado] = useState(null);
    const [cartas, setCartas] = useState([]);
    const [mostrarModal, setMostrarModal] = useState(false);

    //Estado para controlar el mazo
    const [mazos, setMazos] = useState([]);

    //Estados para editar nombre
    const [nuevosNombres, setNuevosNombres] = useState({});

    //Para errores en el nombre
    const [erroresNombre, setErroresNombre] = useState({});

    // Estado para manejar el loading al iniciar partida
    const [isStartingGame, setIsStartingGame] = useState(false);
    const [gameStartError, setGameStartError] = useState(null);


    const verMazo = async (mazoId) => {
        try{
            const res = await getCartasDeMazo(userId, mazoId, token);
            setCartas(res.data.cartas);
            console.log("Cartas del mazo:", res.data.cartas);
            setMazoSeleccionado(mazoId);
            setMostrarModal(true);
        } catch (error) {
            console.error("Error al obtener cartas del mazo:", error);
            alert("Error al cargar las cartas del mazo.");
        }
    };

    const editarNombre = async (mazoId) => {
        const nuevoNombre = nuevosNombres[mazoId]?.trim();

        if(!nuevoNombre){
            setErroresNombre({ ...erroresNombre, [mazoId]: "El nombre no puede estar vacío" });
            return;
        }

        if (nuevoNombre.length > 30) {
            setErroresNombre({ ...erroresNombre, [mazoId]: "El nombre no puede tener más de 30 caracteres" });
            setNuevosNombres({ ...nuevosNombres, [mazoId]: "" });
            return;
        }

          try {
            await editarNombreMazo(mazoId, nuevoNombre, token);
            alert("Nombre actualizado correctamente");

            // Volver a cargar los mazos para reflejar el cambio
            const res = await getUserMazos(userId, token);
            setMazos(res.data["lista de mazos del usuario logueado"]);

            // Limpiar el input y error
            setNuevosNombres({ ...nuevosNombres, [mazoId]: null });
            setErroresNombre({ ...erroresNombre, [mazoId]: null });
        } catch (error) {
            console.error("Error al editar el nombre del mazo:", error);
            alert("No se pudo actualizar el nombre del mazo");
        }
    };

    const eliminar = async (mazoId) => {
        // Reemplazamos window.confirm con un modal personalizado si lo tienes
        if (!window.confirm("¿Estás seguro de que deseas eliminar este mazo?")) return;
        try {
            await eliminarMazo(mazoId, token);
            alert("Mazo eliminado correctamente.");

            // Refrescar lista
            const res = await getUserMazos(userId, token);
            setMazos(res.data["lista de mazos del usuario logueado"]);
        } catch (error) {
            console.error("Error al eliminar el mazo:", error);
            const msg = error.response?.data?.error || "No se pudo eliminar el mazo.";
            alert(msg);
        }
    };

    // FUNCIÓN: Iniciar una partida
    const handleIniciarPartida = async (mazoId) => {
        setIsStartingGame(true); // Activar estado de carga
        setGameStartError(null); // Limpiar errores previos

        if (!token) {
            setGameStartError("No estás autenticado. Por favor, inicia sesión.");
            setIsStartingGame(false);
            navigate('/login'); // Redirigir al login si no hay token
            return;
        }

        try {
            // 1. Iniciar la partida en el backend
            const partidaResponse = await iniciarPartida(mazoId, token);
            const { id_partida: idPartida, cartas: cartasEnMano } = partidaResponse.data;
            console.log(partidaResponse.data);

            // 2. Redirigir a JugarPage, pasando el id de la partida y las cartas en mano
            navigate(`/jugar/${idPartida}`, {
                state: {
                    cartasEnMano: cartasEnMano // Pasamos las cartas para evitar un fetch adicional
                }
            });

        } catch (error) {
            console.error("Error al iniciar la partida:", error.response?.data || error.message);
            setGameStartError(error.response?.data?.error || "Error al iniciar la partida. Intenta de nuevo.");
        } finally {
            setIsStartingGame(false); // Desactivar estado de carga
        }
    };

    useEffect(() => {
        const cargarMazos = async () => {
            if (!userId || !token) {
                console.warn("No hay userId o token, no se cargarán los mazos.");
                return;
            }
            try {
                const res = await getUserMazos(userId, token);
                setMazos(res.data["lista de mazos del usuario logueado"]);
            } catch (error) {
                if (error.response?.status === 400) {
                    setMazos([]); // lo tratamos como "sin mazos"
                } else {
                    console.error("Error al cargar los mazos:", error);
                    // Considera mostrar un mensaje de error al usuario
                }
            }
        };

        cargarMazos();
    }, [userId, token]); // Dependencias para recargar si cambian userId o token

    return (
        <>
        <Layout>
            <div className="container mis-mazos-container d-flex justify-content-center">
                <div className="leaderboard-card w-100" style={{ maxWidth: '900px' }}>
                <div className="leaderboard-header">MIS MAZOS</div>

                {/* Mostrar error al iniciar partida si existe */}
                {gameStartError && (
                    <div className="alert alert-danger" role="alert">
                        {gameStartError}
                    </div>
                )}
                {isStartingGame && (
                    <div className="alert alert-info" role="alert">
                        Iniciando partida... por favor espera.
                    </div>
                )}

                {mazos.length === 0 && (
                    <p className="sin-mazos">Aún no tenes asignado ningún mazo</p>
                )}


                {mazos.length > 0 && (
                <div>
                    <div className="leaderboard-row header static-header">
                        <div>MAZO</div>
                        <div>ACCIONES</div>
                        <div>NUEVO NOMBRE</div>   
                        <div>JUGAR</div>
                    </div>

                    {mazos.map((mazo) => (
                    <div key={mazo.id} className="leaderboard-row">
                        <div>{mazo.nombre} </div>
                        <div  className="acciones">
                            <button className="btn" onClick={() => verMazo(mazo.id)}>Ver mazo</button>
                            <button className="btn" onClick={() => eliminar(mazo.id)}>Eliminar</button>
                            <button className="btn" onClick={() => editarNombre(mazo.id)}>Editar</button>
                        </div>
                        <div>
                            <input 
                                type="text" 
                                placeholder="Nuevo nombre" 
                                className="input" 
                                value={nuevosNombres[mazo.id] || ""}
                                onChange={(e) => setNuevosNombres({...nuevosNombres, [mazo.id]: e.target.value})}
                            />
                            {erroresNombre[mazo.id] && (
                                <p className="error-mensaje">{erroresNombre[mazo.id]}</p>
                            )}
                        </div>
                        <div>
                            <button 
                                className="btn" 
                                onClick={() => handleIniciarPartida(mazo.id)}
                                disabled={isStartingGame} // Deshabilitar mientras se inicia la partida
                            >
                                {isStartingGame ? 'Iniciando...' : 'Jugar'}
                            </button>
                        </div>
                    </div>
                    ))}
                </div>
                )}
                    <button 
                        className="btn alta" 
                        disabled={mazos.length >= 3}
                        onClick={() => window.location.href = "/crear-mazo"} 
                    >
                        Alta de nuevo mazo
                    </button>

                </div>
            </div>


        </Layout>
        
        {mostrarModal && (
        <Modal title={`CARTAS DEL MAZO`} onClose={() => setMostrarModal(false)}>
            <div className="cartas-contenedor">
                {cartas.map((carta, index) => (
                    <div className="carta-item" key={index}>
                        <img
                        src={`https://raw.githubusercontent.com/PokeAPI/sprites/master/sprites/pokemon/${carta.id}.png`}
                        alt={carta.nombre}
                        className="carta-img"
                        onError={(e) => (e.target.src = "/img/placeholder.png")}
                        />
                        <div className="carta-nombre">
                            {carta.nombre}
                        </div>
                    </div>
                ))}
            </div>
        </Modal>
        )}
        </>
    );
}

export default MisMazosPage;
