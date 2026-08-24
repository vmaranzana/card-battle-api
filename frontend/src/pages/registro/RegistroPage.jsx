import { useState } from "react";
import { registerUser } from '../../services/apiServices.js';
import { useNavigate } from "react-router-dom";
import Layout from '../../layout/Layout.jsx';
import '../../assets/styles/Forms.css';

function RegistroPage() {
    const [errores, setErrores] = useState([]);
    const navigate = useNavigate();

    const handleSubmit = async (e) => {
        e.preventDefault();
        const nombre = e.target[0].value.trim();
        const usuario = e.target[1].value.trim();
        const password = e.target[2].value;

        const nuevosErrores = [];

        if (!nombre) {
            nuevosErrores.push("El nombre no puede estar vacío.");
        } else if (nombre.length > 30) {
            nuevosErrores.push("El nombre no puede tener más de 30 caracteres.");
        }

        if (!usuario) {
            nuevosErrores.push("El nombre de usuario no puede estar vacío.");
        } else {
            if (usuario.length < 6 || usuario.length > 20) {
                nuevosErrores.push("El nombre de usuario debe tener entre 6 y 20 caracteres.");
            }
            if (!/^[a-zA-Z0-9]+$/.test(usuario)) {
                nuevosErrores.push("El nombre de usuario solo puede contener letras y números.");
            }
        }

        if (password.length < 8) {
            nuevosErrores.push("La contraseña debe tener al menos 8 caracteres.");
        } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()\-_=+{}[\]:;"'<>,.?/\\|]).{8,}/.test(password)) {
            nuevosErrores.push("La contraseña debe tener mayúsculas, minúsculas, números y caracteres especiales.");
        }

        if (nuevosErrores.length > 0) {
            setErrores(nuevosErrores);
            return;
        }

        const data = { nombre, usuario, password };

        try {
            await registerUser(data);
            console.log("Usuario registrado con éxito");
            setErrores([]);
            navigate("/login"); 
        } catch (error) {
            if (error.response?.data?.error) {
                setErrores([error.response.data.error]);
            } else {
                setErrores(["Error al registrar el usuario."]);
            }
        }
    };

    // Función para manejar el clic del botón "Volver al Inicio"
    const handleGoBack = () => {
        navigate("/"); 
    };

    return (
        <Layout>
            <div className="container d-flex justify-content-center align-items-center min-vh-100 py-5">
                <div className="left p-4 rounded shadow-lg mx-auto col-12 col-md-8 col-lg-6">
                    {errores.length > 0 && (
                        // Componente de alerta de Bootstrap para errores
                        <div className="alert alert-danger" role="alert">
                            <ul className="mb-0 ps-3"> 
                                {errores.map((err, i) => (
                                    <li key={i}>{err}</li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <h2 className="title text-center mb-4">REGISTRAR USUARIO</h2> 

                    <form className='form needs-validation' onSubmit={handleSubmit} noValidate>
                        <div className="mb-3"> 
                            <label htmlFor="nombreInput" className="form-label">Ingresa tu nombre</label>
                            <input
                                type="text"
                                className="form-control"
                                id="nombreInput"
                                name="nombre"
                                placeholder="Tu nombre completo"
                                required
                            />
                        </div>

                        <div className="mb-3"> 
                            <label htmlFor="usuarioInput" className="form-label">Ingresa nombre de usuario</label>
                            <input
                                type="text"
                                className="form-control"
                                id="usuarioInput"
                                name="usuario"
                                placeholder="Nombre de usuario"
                                required
                            />
                        </div>

                        <div className="mb-4"> 
                            <label htmlFor="passwordInput" className="form-label">Ingresa contraseña</label>
                            <input
                                type="password"
                                className="form-control"
                                id="passwordInput"
                                name="password"
                                placeholder="Contraseña"
                                required
                            />
                        </div>

                        <div className="d-flex justify-content-between gap-2 flex-column flex-md-row mt-3">
                            <button type="submit" className="btn btn-primary flex-grow-1">Registrarme</button>
                            <button type="button" className="btn btn-outline-secondary-custom flex-grow-1" onClick={handleGoBack}>
                                Volver al Inicio
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Layout>
    );
}

export default RegistroPage;
