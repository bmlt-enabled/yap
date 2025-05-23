import React from 'react';

class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null, errorInfo: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }

    componentDidCatch(error, errorInfo) {
        this.setState({ errorInfo });
        console.error("ErrorBoundary caught an error", error, errorInfo);
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="flex items-center justify-center min-h-screen bg-gray-900 text-white p-6">
                    <div className="bg-gray-800 rounded-2xl shadow-lg p-8 max-w-md text-center">
                        <h1 className="text-3xl font-bold mb-4 text-red-400">Oops! Something went wrong.</h1>
                        <p className="mb-4 text-gray-300">An unexpected error has occurred. Please try refreshing the page.</p>
                        {this.state.error && (
                            <pre className="bg-gray-700 p-4 rounded-md text-sm text-red-300 overflow-auto max-h-40">
                {this.state.error.toString()}
              </pre>
                        )}
                        <button
                            className="mt-6 bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-xl transition duration-300"
                            onClick={() => window.location.reload()}
                        >
                            Refresh Page
                        </button>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}

export default ErrorBoundary;
